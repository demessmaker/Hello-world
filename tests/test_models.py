"""Tests for the core RRSP calculation models."""

import unittest

from rrsp_calculator.tax import Province
from rrsp_calculator.models import (
    PersonProfile,
    project_rrsp_growth,
    plan_hbp_withdrawal,
    calculate_retirement_income,
    calculate_overcontribution_penalty,
    HBP_MAX_WITHDRAWAL,
)


class TestProjectRRSPGrowth(unittest.TestCase):
    """Tests for RRSP growth projection."""

    def setUp(self):
        self.profile = PersonProfile(
            age=30,
            annual_income=80_000,
            province=Province.ONTARIO,
            existing_rrsp_balance=10_000,
            available_contribution_room=20_000,
            expected_retirement_age=65,
            expected_return_rate=0.06,
        )

    def test_basic_projection(self):
        proj = project_rrsp_growth(self.profile, 10_000, 10)
        self.assertEqual(len(proj.years), 10)
        self.assertGreater(proj.final_balance, 10_000)  # Grew from starting balance
        self.assertGreater(proj.total_contributions, 0)

    def test_projection_balance_grows(self):
        proj = project_rrsp_growth(self.profile, 10_000, 5)
        # Each year's closing balance should exceed the opening
        for yr in proj.years:
            self.assertGreater(yr.closing_balance, yr.opening_balance)

    def test_zero_contribution(self):
        proj = project_rrsp_growth(self.profile, 0, 5)
        # Balance should still grow from investment returns
        self.assertGreater(proj.final_balance, self.profile.existing_rrsp_balance)
        self.assertEqual(proj.total_contributions, 0)

    def test_zero_years(self):
        proj = project_rrsp_growth(self.profile, 10_000, 0)
        self.assertEqual(len(proj.years), 0)

    def test_tax_savings_calculated(self):
        proj = project_rrsp_growth(self.profile, 10_000, 5)
        self.assertGreater(proj.total_tax_savings, 0)

    def test_inflation_adjusted_balance(self):
        proj = project_rrsp_growth(self.profile, 10_000, 30)
        # Real balance should be less than nominal balance
        self.assertLess(proj.real_final_balance, proj.final_balance)

    def test_retirement_default_years(self):
        proj = project_rrsp_growth(self.profile, 10_000)
        # Should project 35 years (age 30 to 65)
        self.assertEqual(len(proj.years), 35)

    def test_investment_growth_property(self):
        proj = project_rrsp_growth(self.profile, 10_000, 10)
        expected_growth = proj.final_balance - proj.total_contributions
        self.assertAlmostEqual(proj.total_investment_growth, expected_growth, places=2)


class TestOvercontributionPenalty(unittest.TestCase):
    """Tests for over-contribution penalty calculation."""

    def test_no_penalty_within_room(self):
        penalty = calculate_overcontribution_penalty(10_000, 15_000)
        self.assertEqual(penalty, 0)

    def test_no_penalty_within_buffer(self):
        # $2,000 over room is within the buffer
        penalty = calculate_overcontribution_penalty(17_000, 15_000)
        self.assertEqual(penalty, 0)

    def test_penalty_over_buffer(self):
        # $5,000 over room, minus $2,000 buffer = $3,000 excess
        penalty = calculate_overcontribution_penalty(20_000, 15_000, months=12)
        expected = 3_000 * 0.01 * 12  # $360
        self.assertAlmostEqual(penalty, expected, places=2)

    def test_penalty_single_month(self):
        penalty = calculate_overcontribution_penalty(20_000, 15_000, months=1)
        expected = 3_000 * 0.01 * 1
        self.assertAlmostEqual(penalty, expected, places=2)


class TestHBPPlan(unittest.TestCase):
    """Tests for Home Buyers' Plan calculations."""

    def test_basic_hbp(self):
        plan = plan_hbp_withdrawal(35_000, 50_000)
        self.assertEqual(plan.withdrawal_amount, 35_000)
        self.assertEqual(len(plan.repayment_schedule), 15)
        self.assertAlmostEqual(plan.total_repaid, 35_000, places=0)

    def test_hbp_capped_at_max(self):
        plan = plan_hbp_withdrawal(100_000, 200_000)
        self.assertEqual(plan.withdrawal_amount, HBP_MAX_WITHDRAWAL)

    def test_hbp_capped_at_balance(self):
        plan = plan_hbp_withdrawal(60_000, 25_000)
        self.assertEqual(plan.withdrawal_amount, 25_000)

    def test_repayment_schedule_correct(self):
        plan = plan_hbp_withdrawal(30_000, 50_000)
        annual_payment = 30_000 / 15
        self.assertAlmostEqual(plan.repayment_per_year, annual_payment, places=2)
        # Last entry should have ~0 remaining
        self.assertAlmostEqual(
            plan.repayment_schedule[-1]["remaining_balance"], 0, places=0
        )


class TestRetirementIncome(unittest.TestCase):
    """Tests for retirement income calculation."""

    def test_basic_income(self):
        income = calculate_retirement_income(500_000, 25, 0.04)
        self.assertGreater(income, 0)
        # Should be more than simple division (due to continued growth)
        self.assertGreater(income, 500_000 / 25)

    def test_zero_balance(self):
        self.assertEqual(calculate_retirement_income(0), 0)

    def test_zero_return(self):
        income = calculate_retirement_income(500_000, 25, 0.0)
        self.assertAlmostEqual(income, 20_000, places=2)

    def test_higher_return_more_income(self):
        low = calculate_retirement_income(500_000, 25, 0.03)
        high = calculate_retirement_income(500_000, 25, 0.06)
        self.assertGreater(high, low)


if __name__ == "__main__":
    unittest.main()
