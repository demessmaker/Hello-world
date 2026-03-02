"""Tests for the tax calculation module."""

import unittest

from rrsp_calculator.tax import (
    Province,
    calculate_bracket_tax,
    calculate_tax,
    calculate_rrsp_tax_savings,
    calculate_rrsp_contribution_room,
    calculate_withdrawal_tax,
    get_marginal_rate,
    FEDERAL_BRACKETS,
    PROVINCIAL_BRACKETS,
    RRSP_MAX_CONTRIBUTION_2025,
)


class TestBracketTax(unittest.TestCase):
    """Tests for progressive bracket tax calculation."""

    def test_zero_income(self):
        self.assertEqual(calculate_bracket_tax(0, FEDERAL_BRACKETS), 0.0)

    def test_negative_income(self):
        self.assertEqual(calculate_bracket_tax(-10000, FEDERAL_BRACKETS), 0.0)

    def test_first_bracket_only(self):
        # $50,000 income, all in first federal bracket (15%)
        tax = calculate_bracket_tax(50_000, FEDERAL_BRACKETS)
        self.assertAlmostEqual(tax, 50_000 * 0.15, places=2)

    def test_two_brackets(self):
        # $80,000 income spans first two federal brackets
        tax = calculate_bracket_tax(80_000, FEDERAL_BRACKETS)
        expected = 57_375 * 0.15 + (80_000 - 57_375) * 0.205
        self.assertAlmostEqual(tax, expected, places=2)

    def test_high_income(self):
        # $300,000 spans all brackets
        tax = calculate_bracket_tax(300_000, FEDERAL_BRACKETS)
        self.assertGreater(tax, 0)
        # Should be less than if all was taxed at top rate
        self.assertLess(tax, 300_000 * 0.33)


class TestMarginalRate(unittest.TestCase):
    """Tests for marginal rate lookup."""

    def test_zero_income(self):
        self.assertEqual(get_marginal_rate(0, FEDERAL_BRACKETS), 0.0)

    def test_first_bracket(self):
        self.assertEqual(get_marginal_rate(30_000, FEDERAL_BRACKETS), 0.15)

    def test_top_bracket(self):
        self.assertEqual(get_marginal_rate(500_000, FEDERAL_BRACKETS), 0.33)

    def test_bracket_boundary(self):
        # At exact boundary of first bracket
        self.assertEqual(get_marginal_rate(57_375, FEDERAL_BRACKETS), 0.15)


class TestCalculateTax(unittest.TestCase):
    """Tests for combined federal + provincial tax."""

    def test_ontario_100k(self):
        result = calculate_tax(100_000, Province.ONTARIO)
        self.assertEqual(result.gross_income, 100_000)
        self.assertGreater(result.federal_tax, 0)
        self.assertGreater(result.provincial_tax, 0)
        self.assertAlmostEqual(
            result.total_tax, result.federal_tax + result.provincial_tax, places=2
        )
        self.assertAlmostEqual(
            result.after_tax_income, 100_000 - result.total_tax, places=2
        )

    def test_effective_rate_reasonable(self):
        result = calculate_tax(100_000, Province.ONTARIO)
        # Effective rate should be between 20% and 40% for $100k in Ontario
        self.assertGreater(result.effective_rate, 0.20)
        self.assertLess(result.effective_rate, 0.40)

    def test_alberta_lower_provincial(self):
        on_result = calculate_tax(80_000, Province.ONTARIO)
        ab_result = calculate_tax(80_000, Province.ALBERTA)
        # Alberta has a flat 10% up to ~$148k, Ontario is 9.15% at $80k
        # Federal tax should be the same
        self.assertEqual(on_result.federal_tax, ab_result.federal_tax)

    def test_all_provinces(self):
        for province in Province:
            result = calculate_tax(75_000, province)
            self.assertGreater(result.total_tax, 0)
            self.assertLess(result.total_tax, 75_000)


class TestRRSPTaxSavings(unittest.TestCase):
    """Tests for RRSP tax savings calculation."""

    def test_positive_savings(self):
        savings = calculate_rrsp_tax_savings(100_000, 10_000, Province.ONTARIO)
        self.assertGreater(savings, 0)

    def test_savings_proportional(self):
        # Larger contribution = larger savings
        small = calculate_rrsp_tax_savings(100_000, 5_000, Province.ONTARIO)
        large = calculate_rrsp_tax_savings(100_000, 15_000, Province.ONTARIO)
        self.assertGreater(large, small)

    def test_zero_contribution(self):
        savings = calculate_rrsp_tax_savings(100_000, 0, Province.ONTARIO)
        self.assertEqual(savings, 0)

    def test_savings_less_than_contribution(self):
        # Tax savings should always be less than the contribution
        savings = calculate_rrsp_tax_savings(100_000, 20_000, Province.ONTARIO)
        self.assertLess(savings, 20_000)


class TestContributionRoom(unittest.TestCase):
    """Tests for RRSP contribution room calculation."""

    def test_basic_room(self):
        room = calculate_rrsp_contribution_room(100_000)
        self.assertEqual(room, 18_000)  # 18% of $100,000

    def test_capped_at_max(self):
        room = calculate_rrsp_contribution_room(500_000)
        self.assertEqual(room, RRSP_MAX_CONTRIBUTION_2025)

    def test_with_pension_adjustment(self):
        room = calculate_rrsp_contribution_room(100_000, 5_000)
        self.assertEqual(room, 13_000)  # 18,000 - 5,000

    def test_zero_income(self):
        room = calculate_rrsp_contribution_room(0)
        self.assertEqual(room, 0)


class TestWithdrawalTax(unittest.TestCase):
    """Tests for RRSP withdrawal withholding tax."""

    def test_small_withdrawal(self):
        tax = calculate_withdrawal_tax(3_000, Province.ONTARIO)
        self.assertAlmostEqual(tax, 300, places=2)  # 10%

    def test_medium_withdrawal(self):
        tax = calculate_withdrawal_tax(10_000, Province.ONTARIO)
        self.assertAlmostEqual(tax, 2_000, places=2)  # 20%

    def test_large_withdrawal(self):
        tax = calculate_withdrawal_tax(25_000, Province.ONTARIO)
        self.assertAlmostEqual(tax, 7_500, places=2)  # 30%

    def test_quebec_different_rates(self):
        # Quebec has lower federal withholding
        qc_tax = calculate_withdrawal_tax(10_000, Province.QUEBEC)
        on_tax = calculate_withdrawal_tax(10_000, Province.ONTARIO)
        self.assertLess(qc_tax, on_tax)


if __name__ == "__main__":
    unittest.main()
