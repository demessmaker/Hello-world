"""Core RRSP calculation engine - projections, growth, and contribution modeling."""

from dataclasses import dataclass, field
from typing import List, Optional

from .tax import (
    Province,
    calculate_rrsp_contribution_room,
    calculate_rrsp_tax_savings,
    calculate_tax,
    HBP_MAX_WITHDRAWAL,
    HBP_REPAYMENT_YEARS,
    RRSP_CONTRIBUTION_RATE,
    RRSP_CONVERSION_AGE,
    RRSP_MAX_CONTRIBUTION_2025,
    RRSP_OVERCONTRIBUTION_BUFFER,
    RRSP_OVERCONTRIBUTION_PENALTY_RATE,
)


@dataclass
class PersonProfile:
    """Represents a person's financial profile for RRSP planning."""
    age: int
    annual_income: float
    province: Province
    existing_rrsp_balance: float = 0.0
    available_contribution_room: float = 0.0
    pension_adjustment: float = 0.0
    expected_retirement_age: int = 65
    expected_annual_raise: float = 0.02  # 2% annual income growth
    expected_return_rate: float = 0.06  # 6% annual investment return
    expected_inflation_rate: float = 0.02  # 2% inflation


@dataclass
class YearProjection:
    """Projection data for a single year."""
    year: int
    age: int
    income: float
    contribution: float
    contribution_room_used: float
    tax_savings: float
    opening_balance: float
    investment_return: float
    closing_balance: float
    cumulative_contributions: float
    cumulative_tax_savings: float


@dataclass
class RRSPProjection:
    """Complete RRSP growth projection over multiple years."""
    profile: PersonProfile
    years: List[YearProjection] = field(default_factory=list)
    total_contributions: float = 0.0
    total_tax_savings: float = 0.0
    final_balance: float = 0.0
    real_final_balance: float = 0.0  # Inflation-adjusted

    @property
    def total_investment_growth(self) -> float:
        return self.final_balance - self.total_contributions


@dataclass
class HBPPlan:
    """Home Buyers' Plan withdrawal and repayment schedule."""
    withdrawal_amount: float
    repayment_per_year: float
    repayment_schedule: List[dict] = field(default_factory=list)
    total_repaid: float = 0.0
    added_to_income: float = 0.0  # Missed repayments added to income


def project_rrsp_growth(
    profile: PersonProfile,
    annual_contribution: Optional[float] = None,
    years: Optional[int] = None,
) -> RRSPProjection:
    """Project RRSP growth over time with fixed annual contributions.

    Args:
        profile: Person's financial profile.
        annual_contribution: Fixed annual contribution. If None, uses max room.
        years: Number of years to project. If None, projects to retirement age.
    """
    if years is None:
        years = profile.expected_retirement_age - profile.age

    if years <= 0:
        return RRSPProjection(profile=profile)

    projection = RRSPProjection(profile=profile)
    balance = profile.existing_rrsp_balance
    income = profile.annual_income
    room = profile.available_contribution_room
    cumulative_contrib = 0.0
    cumulative_savings = 0.0

    for yr in range(years):
        age = profile.age + yr
        opening = balance

        # If past conversion age, no more contributions
        if age > RRSP_CONVERSION_AGE:
            contribution = 0.0
        elif annual_contribution is not None:
            contribution = min(annual_contribution, room)
        else:
            # Maximize contribution up to available room
            new_room = calculate_rrsp_contribution_room(
                income, profile.pension_adjustment
            )
            room += new_room
            contribution = min(room, RRSP_MAX_CONTRIBUTION_2025)

        contribution = max(contribution, 0)

        # Tax savings from this contribution
        tax_savings = calculate_rrsp_tax_savings(income, contribution, profile.province)

        # Investment growth on the balance (applied to mid-year average)
        mid_year_balance = opening + contribution / 2
        investment_return = mid_year_balance * profile.expected_return_rate
        balance = opening + contribution + investment_return

        # Track room usage
        if annual_contribution is not None:
            new_room = calculate_rrsp_contribution_room(
                income, profile.pension_adjustment
            )
            room = room + new_room - contribution
        else:
            room -= contribution

        room = max(room, 0)

        cumulative_contrib += contribution
        cumulative_savings += tax_savings

        projection.years.append(
            YearProjection(
                year=yr + 1,
                age=age,
                income=round(income, 2),
                contribution=round(contribution, 2),
                contribution_room_used=round(contribution, 2),
                tax_savings=round(tax_savings, 2),
                opening_balance=round(opening, 2),
                investment_return=round(investment_return, 2),
                closing_balance=round(balance, 2),
                cumulative_contributions=round(cumulative_contrib, 2),
                cumulative_tax_savings=round(cumulative_savings, 2),
            )
        )

        # Income grows annually
        income *= 1 + profile.expected_annual_raise

    projection.total_contributions = round(cumulative_contrib, 2)
    projection.total_tax_savings = round(cumulative_savings, 2)
    projection.final_balance = round(balance, 2)

    # Calculate inflation-adjusted final balance
    inflation_factor = (1 + profile.expected_inflation_rate) ** years
    projection.real_final_balance = round(balance / inflation_factor, 2)

    return projection


def calculate_overcontribution_penalty(
    total_contributions: float, contribution_room: float, months: int = 12
) -> float:
    """Calculate penalty for RRSP over-contributions.

    You can over-contribute up to $2,000 without penalty.
    Beyond that, the penalty is 1% per month on the excess.
    """
    excess = total_contributions - contribution_room - RRSP_OVERCONTRIBUTION_BUFFER
    if excess <= 0:
        return 0.0
    return round(excess * RRSP_OVERCONTRIBUTION_PENALTY_RATE * months, 2)


def plan_hbp_withdrawal(
    withdrawal_amount: float, current_rrsp_balance: float
) -> HBPPlan:
    """Plan a Home Buyers' Plan withdrawal and repayment schedule.

    Args:
        withdrawal_amount: Amount to withdraw (max $60,000).
        current_rrsp_balance: Current RRSP balance to validate against.
    """
    amount = min(withdrawal_amount, HBP_MAX_WITHDRAWAL, current_rrsp_balance)
    annual_repayment = amount / HBP_REPAYMENT_YEARS

    plan = HBPPlan(
        withdrawal_amount=amount,
        repayment_per_year=round(annual_repayment, 2),
    )

    remaining = amount
    for year in range(1, HBP_REPAYMENT_YEARS + 1):
        repayment = min(annual_repayment, remaining)
        remaining -= repayment
        plan.repayment_schedule.append(
            {
                "year": year,
                "repayment_due": round(repayment, 2),
                "remaining_balance": round(max(remaining, 0), 2),
            }
        )
        plan.total_repaid += repayment

    plan.total_repaid = round(plan.total_repaid, 2)
    return plan


def calculate_retirement_income(
    rrsp_balance: float,
    years_in_retirement: int = 25,
    return_rate: float = 0.04,
) -> float:
    """Calculate sustainable annual withdrawal from RRSP/RRIF in retirement.

    Uses the annuity formula for level payments over a fixed period.
    """
    if rrsp_balance <= 0 or years_in_retirement <= 0:
        return 0.0

    if return_rate == 0:
        return round(rrsp_balance / years_in_retirement, 2)

    # Annuity payment formula: PMT = PV * r / (1 - (1+r)^-n)
    r = return_rate
    n = years_in_retirement
    payment = rrsp_balance * r / (1 - (1 + r) ** -n)
    return round(payment, 2)
