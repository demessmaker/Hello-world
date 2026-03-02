"""RRSP saving strategy comparison and optimization."""

from dataclasses import dataclass, field
from typing import List, Optional

from .models import (
    PersonProfile,
    RRSPProjection,
    project_rrsp_growth,
    calculate_retirement_income,
)
from .tax import (
    Province,
    calculate_tax,
    calculate_rrsp_tax_savings,
    calculate_rrsp_contribution_room,
    RRSP_MAX_CONTRIBUTION_2025,
)


@dataclass
class StrategyResult:
    """Result of a single strategy analysis."""
    name: str
    description: str
    projection: RRSPProjection
    annual_retirement_income: float
    score: float = 0.0  # Comparative score (higher is better)


@dataclass
class StrategyComparison:
    """Side-by-side comparison of multiple RRSP strategies."""
    profile: PersonProfile
    strategies: List[StrategyResult] = field(default_factory=list)
    recommended: Optional[str] = None

    def get_best(self) -> Optional[StrategyResult]:
        if not self.strategies:
            return None
        return max(self.strategies, key=lambda s: s.score)


@dataclass
class RRSPvsTFSA:
    """Comparison between RRSP and TFSA outcomes."""
    rrsp_final_balance: float
    rrsp_after_tax_balance: float
    tfsa_final_balance: float
    rrsp_tax_savings_reinvested: float
    recommendation: str
    marginal_rate_now: float
    expected_marginal_rate_retirement: float


def strategy_maximize_early(profile: PersonProfile) -> StrategyResult:
    """Strategy: Front-load contributions in early years.

    Contribute the maximum possible in the first 10 years,
    then reduce to a maintenance level.
    """
    years = profile.expected_retirement_age - profile.age
    if years <= 0:
        proj = project_rrsp_growth(profile, 0, 0)
        return StrategyResult(
            name="Maximize Early",
            description="Front-load maximum contributions in early years",
            projection=proj,
            annual_retirement_income=0,
        )

    # Phase 1: Max contributions for first 10 years (or until retirement)
    phase1_years = min(10, years)
    phase1 = project_rrsp_growth(profile, RRSP_MAX_CONTRIBUTION_2025, phase1_years)

    # Phase 2: Reduced contributions for remaining years
    if years > phase1_years:
        phase2_profile = PersonProfile(
            age=profile.age + phase1_years,
            annual_income=profile.annual_income * (1 + profile.expected_annual_raise) ** phase1_years,
            province=profile.province,
            existing_rrsp_balance=phase1.final_balance,
            available_contribution_room=5000,  # Reduced steady contribution
            pension_adjustment=profile.pension_adjustment,
            expected_retirement_age=profile.expected_retirement_age,
            expected_annual_raise=profile.expected_annual_raise,
            expected_return_rate=profile.expected_return_rate,
            expected_inflation_rate=profile.expected_inflation_rate,
        )
        phase2 = project_rrsp_growth(
            phase2_profile, 5000, years - phase1_years
        )
        # Combine projections
        combined_years = phase1.years + phase2.years
        final_balance = phase2.final_balance
        total_contributions = phase1.total_contributions + phase2.total_contributions
        total_savings = phase1.total_tax_savings + phase2.total_tax_savings
    else:
        combined_years = phase1.years
        final_balance = phase1.final_balance
        total_contributions = phase1.total_contributions
        total_savings = phase1.total_tax_savings

    combined = RRSPProjection(
        profile=profile,
        years=combined_years,
        total_contributions=total_contributions,
        total_tax_savings=total_savings,
        final_balance=final_balance,
    )

    retirement_income = calculate_retirement_income(final_balance)

    return StrategyResult(
        name="Maximize Early",
        description="Front-load maximum contributions in early years, then reduce",
        projection=combined,
        annual_retirement_income=retirement_income,
        score=final_balance,
    )


def strategy_steady_contributions(
    profile: PersonProfile, contribution_rate: float = 0.10
) -> StrategyResult:
    """Strategy: Contribute a steady percentage of income each year."""
    annual_contribution = profile.annual_income * contribution_rate
    years = profile.expected_retirement_age - profile.age

    proj = project_rrsp_growth(profile, annual_contribution, years)
    retirement_income = calculate_retirement_income(proj.final_balance)

    return StrategyResult(
        name=f"Steady {contribution_rate:.0%}",
        description=f"Contribute {contribution_rate:.0%} of income consistently each year",
        projection=proj,
        annual_retirement_income=retirement_income,
        score=proj.final_balance,
    )


def strategy_catch_up(
    profile: PersonProfile, catch_up_years: int = 5
) -> StrategyResult:
    """Strategy: Aggressive catch-up contributions to use accumulated room.

    Maximizes contributions for a set number of years to use up
    existing contribution room, then switches to steady contributions.
    """
    years = profile.expected_retirement_age - profile.age
    if years <= 0:
        proj = project_rrsp_growth(profile, 0, 0)
        return StrategyResult(
            name="Catch-Up",
            description="Aggressive catch-up then steady",
            projection=proj,
            annual_retirement_income=0,
        )

    actual_catch_up = min(catch_up_years, years)

    # Phase 1: Max out for catch-up period
    phase1 = project_rrsp_growth(profile, RRSP_MAX_CONTRIBUTION_2025, actual_catch_up)

    # Phase 2: Moderate steady contributions
    if years > actual_catch_up:
        future_income = profile.annual_income * (1 + profile.expected_annual_raise) ** actual_catch_up
        steady_amount = future_income * 0.10
        phase2_profile = PersonProfile(
            age=profile.age + actual_catch_up,
            annual_income=future_income,
            province=profile.province,
            existing_rrsp_balance=phase1.final_balance,
            available_contribution_room=calculate_rrsp_contribution_room(future_income),
            pension_adjustment=profile.pension_adjustment,
            expected_retirement_age=profile.expected_retirement_age,
            expected_annual_raise=profile.expected_annual_raise,
            expected_return_rate=profile.expected_return_rate,
            expected_inflation_rate=profile.expected_inflation_rate,
        )
        phase2 = project_rrsp_growth(
            phase2_profile, steady_amount, years - actual_catch_up
        )
        final_balance = phase2.final_balance
        total_contributions = phase1.total_contributions + phase2.total_contributions
        total_savings = phase1.total_tax_savings + phase2.total_tax_savings
        combined_years = phase1.years + phase2.years
    else:
        final_balance = phase1.final_balance
        total_contributions = phase1.total_contributions
        total_savings = phase1.total_tax_savings
        combined_years = phase1.years

    combined = RRSPProjection(
        profile=profile,
        years=combined_years,
        total_contributions=total_contributions,
        total_tax_savings=total_savings,
        final_balance=final_balance,
    )

    retirement_income = calculate_retirement_income(final_balance)

    return StrategyResult(
        name="Catch-Up",
        description=f"Max contributions for {actual_catch_up} years, then 10% of income",
        projection=combined,
        annual_retirement_income=retirement_income,
        score=final_balance,
    )


def strategy_tax_optimized(profile: PersonProfile) -> StrategyResult:
    """Strategy: Optimize contributions based on marginal tax rate.

    Contribute more in high-income years (higher marginal rate = bigger
    tax savings) and less in lower-income years.
    """
    years = profile.expected_retirement_age - profile.age
    if years <= 0:
        proj = project_rrsp_growth(profile, 0, 0)
        return StrategyResult(
            name="Tax-Optimized",
            description="Vary contributions based on marginal tax rate",
            projection=proj,
            annual_retirement_income=0,
        )

    tax_result = calculate_tax(profile.annual_income, profile.province)
    combined_rate = tax_result.marginal_rate_federal + tax_result.marginal_rate_provincial

    # Scale contribution based on marginal rate
    # Higher marginal rate = contribute more (better tax savings)
    if combined_rate >= 0.45:
        contribution = RRSP_MAX_CONTRIBUTION_2025
    elif combined_rate >= 0.35:
        contribution = RRSP_MAX_CONTRIBUTION_2025 * 0.75
    elif combined_rate >= 0.25:
        contribution = RRSP_MAX_CONTRIBUTION_2025 * 0.50
    else:
        contribution = RRSP_MAX_CONTRIBUTION_2025 * 0.30

    proj = project_rrsp_growth(profile, contribution, years)
    retirement_income = calculate_retirement_income(proj.final_balance)

    return StrategyResult(
        name="Tax-Optimized",
        description=f"Contribute based on {combined_rate:.1%} marginal rate",
        projection=proj,
        annual_retirement_income=retirement_income,
        score=proj.final_balance * (1 + combined_rate * 0.1),  # Bonus for tax efficiency
    )


def compare_strategies(profile: PersonProfile) -> StrategyComparison:
    """Run all strategies and compare them side-by-side."""
    comparison = StrategyComparison(profile=profile)

    strategies = [
        strategy_maximize_early(profile),
        strategy_steady_contributions(profile, 0.10),
        strategy_steady_contributions(profile, 0.15),
        strategy_catch_up(profile),
        strategy_tax_optimized(profile),
    ]

    comparison.strategies = strategies
    best = comparison.get_best()
    if best:
        comparison.recommended = best.name

    return comparison


def compare_rrsp_vs_tfsa(
    profile: PersonProfile,
    annual_contribution: float,
    years: Optional[int] = None,
    retirement_income: float = 40_000,
) -> RRSPvsTFSA:
    """Compare RRSP vs TFSA for a given contribution amount.

    Key difference:
    - RRSP: Tax deduction now, taxed on withdrawal
    - TFSA: No deduction now, tax-free on withdrawal

    RRSP is better when your marginal rate now > marginal rate in retirement.
    """
    if years is None:
        years = profile.expected_retirement_age - profile.age

    # RRSP projection
    rrsp_proj = project_rrsp_growth(profile, annual_contribution, years)
    rrsp_balance = rrsp_proj.final_balance

    # Estimate after-tax RRSP balance (taxed at retirement marginal rate)
    retirement_tax = calculate_tax(retirement_income, profile.province)
    retirement_marginal = (
        retirement_tax.marginal_rate_federal + retirement_tax.marginal_rate_provincial
    )
    rrsp_after_tax = rrsp_balance * (1 - retirement_marginal)

    # TFSA: Same contributions but no tax deduction
    # However, the tax savings from RRSP could be reinvested in TFSA
    current_tax = calculate_tax(profile.annual_income, profile.province)
    current_marginal = (
        current_tax.marginal_rate_federal + current_tax.marginal_rate_provincial
    )

    # TFSA grows tax-free at the same rate
    tfsa_balance = 0.0
    for yr in range(years):
        tfsa_balance = (tfsa_balance + annual_contribution) * (
            1 + profile.expected_return_rate
        )

    # Tax savings from RRSP reinvested
    annual_tax_savings = annual_contribution * current_marginal
    reinvested_savings = 0.0
    for yr in range(years):
        reinvested_savings = (reinvested_savings + annual_tax_savings) * (
            1 + profile.expected_return_rate
        )

    if current_marginal > retirement_marginal:
        recommendation = (
            "RRSP is likely better: your current marginal rate "
            f"({current_marginal:.1%}) is higher than your expected "
            f"retirement rate ({retirement_marginal:.1%})"
        )
    elif current_marginal < retirement_marginal:
        recommendation = (
            "TFSA is likely better: your current marginal rate "
            f"({current_marginal:.1%}) is lower than your expected "
            f"retirement rate ({retirement_marginal:.1%})"
        )
    else:
        recommendation = (
            "RRSP and TFSA are roughly equivalent at your current "
            f"marginal rate ({current_marginal:.1%})"
        )

    return RRSPvsTFSA(
        rrsp_final_balance=round(rrsp_balance, 2),
        rrsp_after_tax_balance=round(rrsp_after_tax, 2),
        tfsa_final_balance=round(tfsa_balance, 2),
        rrsp_tax_savings_reinvested=round(reinvested_savings, 2),
        recommendation=recommendation,
        marginal_rate_now=current_marginal,
        expected_marginal_rate_retirement=retirement_marginal,
    )
