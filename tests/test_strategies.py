"""Tests for the strategy comparison module."""

import unittest

from rrsp_calculator.tax import Province
from rrsp_calculator.models import PersonProfile
from rrsp_calculator.strategies import (
    strategy_maximize_early,
    strategy_steady_contributions,
    strategy_catch_up,
    strategy_tax_optimized,
    compare_strategies,
    compare_rrsp_vs_tfsa,
)


class TestStrategies(unittest.TestCase):
    """Tests for individual strategies."""

    def setUp(self):
        self.profile = PersonProfile(
            age=30,
            annual_income=90_000,
            province=Province.ONTARIO,
            existing_rrsp_balance=15_000,
            available_contribution_room=30_000,
            expected_retirement_age=65,
            expected_return_rate=0.06,
        )

    def test_maximize_early(self):
        result = strategy_maximize_early(self.profile)
        self.assertEqual(result.name, "Maximize Early")
        self.assertGreater(result.projection.final_balance, 0)
        self.assertGreater(result.annual_retirement_income, 0)

    def test_steady_contributions(self):
        result = strategy_steady_contributions(self.profile, 0.10)
        self.assertIn("10%", result.name)
        self.assertGreater(result.projection.total_contributions, 0)

    def test_higher_rate_more_money(self):
        low = strategy_steady_contributions(self.profile, 0.05)
        high = strategy_steady_contributions(self.profile, 0.15)
        self.assertGreater(
            high.projection.final_balance, low.projection.final_balance
        )

    def test_catch_up(self):
        result = strategy_catch_up(self.profile)
        self.assertEqual(result.name, "Catch-Up")
        self.assertGreater(result.projection.final_balance, 0)

    def test_tax_optimized(self):
        result = strategy_tax_optimized(self.profile)
        self.assertEqual(result.name, "Tax-Optimized")
        self.assertGreater(result.score, 0)

    def test_already_retired(self):
        retired = PersonProfile(
            age=66,
            annual_income=30_000,
            province=Province.ONTARIO,
            expected_retirement_age=65,
        )
        result = strategy_maximize_early(retired)
        self.assertEqual(result.annual_retirement_income, 0)


class TestCompareStrategies(unittest.TestCase):
    """Tests for strategy comparison."""

    def setUp(self):
        self.profile = PersonProfile(
            age=35,
            annual_income=100_000,
            province=Province.ONTARIO,
            existing_rrsp_balance=20_000,
            available_contribution_room=40_000,
            expected_retirement_age=65,
        )

    def test_comparison_returns_all_strategies(self):
        comparison = compare_strategies(self.profile)
        self.assertEqual(len(comparison.strategies), 5)

    def test_has_recommendation(self):
        comparison = compare_strategies(self.profile)
        self.assertIsNotNone(comparison.recommended)

    def test_get_best(self):
        comparison = compare_strategies(self.profile)
        best = comparison.get_best()
        self.assertIsNotNone(best)
        self.assertEqual(best.name, comparison.recommended)


class TestRRSPvsTFSA(unittest.TestCase):
    """Tests for RRSP vs TFSA comparison."""

    def setUp(self):
        self.profile = PersonProfile(
            age=30,
            annual_income=100_000,
            province=Province.ONTARIO,
            expected_retirement_age=65,
            expected_return_rate=0.06,
        )

    def test_comparison_returns_values(self):
        result = compare_rrsp_vs_tfsa(self.profile, 10_000)
        self.assertGreater(result.rrsp_final_balance, 0)
        self.assertGreater(result.tfsa_final_balance, 0)
        self.assertGreater(result.rrsp_after_tax_balance, 0)

    def test_has_recommendation(self):
        result = compare_rrsp_vs_tfsa(self.profile, 10_000)
        self.assertIn("better", result.recommendation.lower())

    def test_high_income_favors_rrsp(self):
        high_income = PersonProfile(
            age=30,
            annual_income=200_000,
            province=Province.ONTARIO,
            expected_retirement_age=65,
            expected_return_rate=0.06,
        )
        result = compare_rrsp_vs_tfsa(
            high_income, 10_000, retirement_income=40_000
        )
        self.assertIn("RRSP", result.recommendation)


class TestCLI(unittest.TestCase):
    """Tests for CLI argument parsing and command execution."""

    def test_tax_command(self):
        from rrsp_calculator.main import main
        # Should not raise
        main(["tax", "--income", "100000", "--province", "ON"])

    def test_project_command(self):
        from rrsp_calculator.main import main
        main([
            "project", "--income", "80000", "--age", "30",
            "--province", "ON", "--years", "5", "--contribution", "10000",
        ])

    def test_compare_command(self):
        from rrsp_calculator.main import main
        main([
            "compare", "--income", "90000", "--age", "35", "--province", "ON",
        ])

    def test_hbp_command(self):
        from rrsp_calculator.main import main
        main(["hbp", "--amount", "35000", "--balance", "50000"])


if __name__ == "__main__":
    unittest.main()
