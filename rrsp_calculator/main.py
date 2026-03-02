"""CLI interface for the RRSP Savings Strategy Calculator."""

import argparse
import sys
from typing import Optional

from .tax import Province, calculate_tax, calculate_rrsp_tax_savings, calculate_withdrawal_tax
from .models import (
    PersonProfile,
    project_rrsp_growth,
    plan_hbp_withdrawal,
    calculate_retirement_income,
    calculate_overcontribution_penalty,
)
from .strategies import compare_strategies, compare_rrsp_vs_tfsa


PROVINCE_CHOICES = [p.value for p in Province]


def format_currency(amount: float) -> str:
    """Format a number as Canadian currency."""
    if amount < 0:
        return f"-${abs(amount):,.2f}"
    return f"${amount:,.2f}"


def format_percent(rate: float) -> str:
    """Format a decimal as a percentage."""
    return f"{rate:.2%}"


def print_header(title: str) -> None:
    """Print a formatted section header."""
    width = 60
    print()
    print("=" * width)
    print(f"  {title}")
    print("=" * width)


def print_row(label: str, value: str, indent: int = 2) -> None:
    """Print a formatted label-value row."""
    padding = " " * indent
    print(f"{padding}{label:<36} {value:>20}")


def cmd_tax(args: argparse.Namespace) -> None:
    """Handle the 'tax' subcommand - show tax breakdown."""
    province = Province(args.province)
    result = calculate_tax(args.income, province)

    print_header(f"Tax Breakdown - {province.name} ({province.value})")
    print_row("Gross Income:", format_currency(result.gross_income))
    print()
    print_row("Federal Tax:", format_currency(result.federal_tax))
    print_row("Provincial Tax:", format_currency(result.provincial_tax))
    print_row("Total Tax:", format_currency(result.total_tax))
    print()
    print_row("Effective Tax Rate:", format_percent(result.effective_rate))
    print_row("Marginal Rate (Federal):", format_percent(result.marginal_rate_federal))
    print_row("Marginal Rate (Provincial):", format_percent(result.marginal_rate_provincial))
    print_row("Marginal Rate (Combined):", format_percent(result.marginal_rate_combined))
    print()
    print_row("After-Tax Income:", format_currency(result.after_tax_income))

    if args.rrsp_contribution:
        savings = calculate_rrsp_tax_savings(args.income, args.rrsp_contribution, province)
        print()
        print_row("RRSP Contribution:", format_currency(args.rrsp_contribution))
        print_row("Tax Savings from RRSP:", format_currency(savings))
        effective_cost = args.rrsp_contribution - savings
        print_row("Effective Cost of RRSP:", format_currency(effective_cost))


def cmd_project(args: argparse.Namespace) -> None:
    """Handle the 'project' subcommand - project RRSP growth."""
    province = Province(args.province)
    profile = PersonProfile(
        age=args.age,
        annual_income=args.income,
        province=province,
        existing_rrsp_balance=args.balance,
        available_contribution_room=args.room,
        expected_retirement_age=args.retirement_age,
        expected_return_rate=args.return_rate / 100,
        expected_annual_raise=args.raise_rate / 100,
        expected_inflation_rate=args.inflation / 100,
    )

    contribution = args.contribution if args.contribution else None
    proj = project_rrsp_growth(profile, contribution, args.years)

    print_header("RRSP Growth Projection")
    print_row("Current Age:", str(profile.age))
    print_row("Retirement Age:", str(profile.expected_retirement_age))
    print_row("Annual Income:", format_currency(profile.annual_income))
    print_row("Province:", province.name)
    print_row("Starting Balance:", format_currency(profile.existing_rrsp_balance))
    print_row("Expected Return:", format_percent(profile.expected_return_rate))
    if contribution:
        print_row("Annual Contribution:", format_currency(contribution))

    # Year-by-year table
    print()
    print(f"  {'Yr':>3} {'Age':>4} {'Contribution':>14} {'Tax Saved':>12} "
          f"{'Return':>12} {'Balance':>16}")
    print(f"  {'---':>3} {'---':>4} {'------------':>14} {'---------':>12} "
          f"{'------':>12} {'-------':>16}")

    for yr in proj.years:
        print(
            f"  {yr.year:>3} {yr.age:>4} "
            f"{format_currency(yr.contribution):>14} "
            f"{format_currency(yr.tax_savings):>12} "
            f"{format_currency(yr.investment_return):>12} "
            f"{format_currency(yr.closing_balance):>16}"
        )

    print()
    print_header("Projection Summary")
    print_row("Total Contributions:", format_currency(proj.total_contributions))
    print_row("Total Tax Savings:", format_currency(proj.total_tax_savings))
    print_row("Investment Growth:", format_currency(proj.total_investment_growth))
    print_row("Final Balance:", format_currency(proj.final_balance))
    print_row("Real Balance (inflation-adj):", format_currency(proj.real_final_balance))

    retirement_income = calculate_retirement_income(proj.final_balance)
    print()
    print_row("Est. Annual Retirement Income:", format_currency(retirement_income))
    print_row("Est. Monthly Retirement Income:", format_currency(retirement_income / 12))


def cmd_compare(args: argparse.Namespace) -> None:
    """Handle the 'compare' subcommand - compare strategies."""
    province = Province(args.province)
    profile = PersonProfile(
        age=args.age,
        annual_income=args.income,
        province=province,
        existing_rrsp_balance=args.balance,
        available_contribution_room=args.room,
        expected_retirement_age=args.retirement_age,
        expected_return_rate=args.return_rate / 100,
        expected_annual_raise=args.raise_rate / 100,
        expected_inflation_rate=args.inflation / 100,
    )

    comparison = compare_strategies(profile)

    print_header("RRSP Strategy Comparison")
    print_row("Age:", str(profile.age))
    print_row("Income:", format_currency(profile.annual_income))
    print_row("Province:", province.name)
    print_row("Years to Retirement:", str(profile.expected_retirement_age - profile.age))

    print()
    header = (
        f"  {'Strategy':<20} {'Final Balance':>16} {'Contributions':>16} "
        f"{'Tax Saved':>12} {'Retire $/yr':>14}"
    )
    print(header)
    print("  " + "-" * (len(header) - 2))

    for s in comparison.strategies:
        marker = " *" if s.name == comparison.recommended else "  "
        print(
            f"{marker}{s.name:<20} "
            f"{format_currency(s.projection.final_balance):>16} "
            f"{format_currency(s.projection.total_contributions):>16} "
            f"{format_currency(s.projection.total_tax_savings):>12} "
            f"{format_currency(s.annual_retirement_income):>14}"
        )

    print()
    print(f"  * Recommended: {comparison.recommended}")

    # Show details for each strategy
    for s in comparison.strategies:
        print()
        print(f"  {s.name}: {s.description}")


def cmd_tfsa(args: argparse.Namespace) -> None:
    """Handle the 'tfsa' subcommand - RRSP vs TFSA comparison."""
    province = Province(args.province)
    profile = PersonProfile(
        age=args.age,
        annual_income=args.income,
        province=province,
        existing_rrsp_balance=args.balance,
        expected_retirement_age=args.retirement_age,
        expected_return_rate=args.return_rate / 100,
    )

    result = compare_rrsp_vs_tfsa(
        profile,
        args.contribution,
        retirement_income=args.retirement_income,
    )

    print_header("RRSP vs TFSA Comparison")
    print_row("Annual Contribution:", format_currency(args.contribution))
    print_row("Current Marginal Rate:", format_percent(result.marginal_rate_now))
    print_row("Retirement Marginal Rate:", format_percent(result.expected_marginal_rate_retirement))

    print()
    print_row("RRSP Final Balance:", format_currency(result.rrsp_final_balance))
    print_row("RRSP After-Tax Balance:", format_currency(result.rrsp_after_tax_balance))
    print_row("RRSP Tax Savings Reinvested:", format_currency(result.rrsp_tax_savings_reinvested))

    print()
    print_row("TFSA Final Balance:", format_currency(result.tfsa_final_balance))

    print()
    print(f"  Recommendation: {result.recommendation}")


def cmd_hbp(args: argparse.Namespace) -> None:
    """Handle the 'hbp' subcommand - Home Buyers' Plan."""
    plan = plan_hbp_withdrawal(args.amount, args.balance)

    print_header("Home Buyers' Plan (HBP)")
    print_row("Withdrawal Amount:", format_currency(plan.withdrawal_amount))
    print_row("Annual Repayment:", format_currency(plan.repayment_per_year))
    print_row("Repayment Period:", "15 years")

    print()
    print(f"  {'Year':>6} {'Repayment Due':>16} {'Remaining':>16}")
    print(f"  {'----':>6} {'-------------':>16} {'---------':>16}")

    for entry in plan.repayment_schedule:
        print(
            f"  {entry['year']:>6} "
            f"{format_currency(entry['repayment_due']):>16} "
            f"{format_currency(entry['remaining_balance']):>16}"
        )


def build_parser() -> argparse.ArgumentParser:
    """Build the CLI argument parser."""
    parser = argparse.ArgumentParser(
        prog="rrsp-calculator",
        description="RRSP Savings Strategy Calculator - Plan your Canadian retirement savings",
    )
    subparsers = parser.add_subparsers(dest="command", help="Available commands")

    # Common arguments
    def add_common_args(p: argparse.ArgumentParser) -> None:
        p.add_argument("--income", type=float, required=True, help="Annual gross income")
        p.add_argument(
            "--province", choices=PROVINCE_CHOICES, default="ON",
            help="Province code (default: ON)",
        )

    def add_profile_args(p: argparse.ArgumentParser) -> None:
        add_common_args(p)
        p.add_argument("--age", type=int, required=True, help="Current age")
        p.add_argument("--balance", type=float, default=0, help="Current RRSP balance")
        p.add_argument("--room", type=float, default=0, help="Available contribution room")
        p.add_argument("--retirement-age", type=int, default=65, help="Target retirement age (default: 65)")
        p.add_argument("--return-rate", type=float, default=6.0, help="Expected annual return %% (default: 6.0)")
        p.add_argument("--raise-rate", type=float, default=2.0, help="Expected annual raise %% (default: 2.0)")
        p.add_argument("--inflation", type=float, default=2.0, help="Expected inflation %% (default: 2.0)")

    # tax command
    tax_parser = subparsers.add_parser("tax", help="Calculate tax breakdown and RRSP savings")
    add_common_args(tax_parser)
    tax_parser.add_argument("--rrsp-contribution", type=float, help="RRSP contribution amount")
    tax_parser.set_defaults(func=cmd_tax)

    # project command
    proj_parser = subparsers.add_parser("project", help="Project RRSP growth over time")
    add_profile_args(proj_parser)
    proj_parser.add_argument("--contribution", type=float, help="Annual contribution (default: maximize)")
    proj_parser.add_argument("--years", type=int, help="Years to project (default: until retirement)")
    proj_parser.set_defaults(func=cmd_project)

    # compare command
    cmp_parser = subparsers.add_parser("compare", help="Compare RRSP saving strategies")
    add_profile_args(cmp_parser)
    cmp_parser.set_defaults(func=cmd_compare)

    # tfsa command
    tfsa_parser = subparsers.add_parser("tfsa", help="Compare RRSP vs TFSA")
    add_profile_args(tfsa_parser)
    tfsa_parser.add_argument("--contribution", type=float, required=True, help="Annual contribution amount")
    tfsa_parser.add_argument("--retirement-income", type=float, default=40000, help="Expected retirement income (default: $40,000)")
    tfsa_parser.set_defaults(func=cmd_tfsa)

    # hbp command
    hbp_parser = subparsers.add_parser("hbp", help="Home Buyers' Plan calculator")
    hbp_parser.add_argument("--amount", type=float, required=True, help="HBP withdrawal amount")
    hbp_parser.add_argument("--balance", type=float, required=True, help="Current RRSP balance")
    hbp_parser.set_defaults(func=cmd_hbp)

    return parser


def main(argv: Optional[list] = None) -> None:
    """Main entry point for the CLI."""
    parser = build_parser()
    args = parser.parse_args(argv)

    if not args.command:
        parser.print_help()
        sys.exit(1)

    args.func(args)


if __name__ == "__main__":
    main()
