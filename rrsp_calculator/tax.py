"""Canadian federal and provincial tax calculations for RRSP planning."""

from dataclasses import dataclass
from enum import Enum
from typing import List, Tuple


class Province(Enum):
    """Supported Canadian provinces."""
    ONTARIO = "ON"
    BRITISH_COLUMBIA = "BC"
    ALBERTA = "AB"
    QUEBEC = "QC"
    SASKATCHEWAN = "SK"
    MANITOBA = "MB"
    NOVA_SCOTIA = "NS"
    NEW_BRUNSWICK = "NB"


# 2025 Federal tax brackets: (upper_limit, rate)
# The last bracket has no upper limit (uses float('inf'))
FEDERAL_BRACKETS: List[Tuple[float, float]] = [
    (57_375, 0.15),
    (114_750, 0.205),
    (158_468, 0.26),
    (221_708, 0.29),
    (float("inf"), 0.33),
]

# 2025 Provincial tax brackets by province
PROVINCIAL_BRACKETS: dict[Province, List[Tuple[float, float]]] = {
    Province.ONTARIO: [
        (52_886, 0.0505),
        (105_775, 0.0915),
        (150_000, 0.1116),
        (220_000, 0.1216),
        (float("inf"), 0.1316),
    ],
    Province.BRITISH_COLUMBIA: [
        (47_937, 0.0506),
        (95_875, 0.077),
        (110_076, 0.105),
        (133_664, 0.1229),
        (181_232, 0.147),
        (252_752, 0.168),
        (float("inf"), 0.205),
    ],
    Province.ALBERTA: [
        (148_269, 0.10),
        (177_922, 0.12),
        (237_230, 0.13),
        (355_845, 0.14),
        (float("inf"), 0.15),
    ],
    Province.QUEBEC: [
        (51_780, 0.14),
        (103_545, 0.19),
        (126_000, 0.24),
        (float("inf"), 0.2575),
    ],
    Province.SASKATCHEWAN: [
        (52_057, 0.105),
        (148_734, 0.125),
        (float("inf"), 0.145),
    ],
    Province.MANITOBA: [
        (47_000, 0.108),
        (100_000, 0.1275),
        (float("inf"), 0.174),
    ],
    Province.NOVA_SCOTIA: [
        (29_590, 0.0879),
        (59_180, 0.1495),
        (93_000, 0.1667),
        (150_000, 0.175),
        (float("inf"), 0.21),
    ],
    Province.NEW_BRUNSWICK: [
        (49_958, 0.094),
        (99_916, 0.14),
        (185_064, 0.16),
        (float("inf"), 0.195),
    ],
}

# RRSP withholding tax rates on withdrawals (outside Quebec)
WITHDRAWAL_TAX_RATES: List[Tuple[float, float]] = [
    (5_000, 0.10),
    (15_000, 0.20),
    (float("inf"), 0.30),
]

# Quebec has different withholding rates (federal portion)
WITHDRAWAL_TAX_RATES_QUEBEC: List[Tuple[float, float]] = [
    (5_000, 0.05),
    (15_000, 0.10),
    (float("inf"), 0.15),
]

# RRSP constants
RRSP_CONTRIBUTION_RATE = 0.18  # 18% of earned income
RRSP_MAX_CONTRIBUTION_2025 = 32_490
RRSP_OVERCONTRIBUTION_BUFFER = 2_000
RRSP_OVERCONTRIBUTION_PENALTY_RATE = 0.01  # 1% per month
RRSP_CONVERSION_AGE = 71  # Must convert to RRIF by end of year turning 71

# Home Buyers' Plan
HBP_MAX_WITHDRAWAL = 60_000  # Increased from $35,000 in 2024
HBP_REPAYMENT_YEARS = 15
HBP_REPAYMENT_START_YEAR = 2  # Repayment starts 2nd year after withdrawal

# Lifelong Learning Plan
LLP_MAX_WITHDRAWAL = 10_000  # Per year
LLP_MAX_TOTAL = 20_000
LLP_REPAYMENT_YEARS = 10


@dataclass
class TaxResult:
    """Result of a tax calculation."""
    gross_income: float
    federal_tax: float
    provincial_tax: float
    total_tax: float
    effective_rate: float
    marginal_rate_federal: float
    marginal_rate_provincial: float
    marginal_rate_combined: float
    after_tax_income: float


def calculate_bracket_tax(income: float, brackets: List[Tuple[float, float]]) -> float:
    """Calculate tax owed given income and a set of progressive tax brackets."""
    if income <= 0:
        return 0.0

    tax = 0.0
    prev_limit = 0.0

    for upper_limit, rate in brackets:
        taxable_in_bracket = min(income, upper_limit) - prev_limit
        if taxable_in_bracket <= 0:
            break
        tax += taxable_in_bracket * rate
        prev_limit = upper_limit

    return round(tax, 2)


def get_marginal_rate(income: float, brackets: List[Tuple[float, float]]) -> float:
    """Get the marginal tax rate for a given income level."""
    if income <= 0:
        return 0.0

    for upper_limit, rate in brackets:
        if income <= upper_limit:
            return rate

    return brackets[-1][1]


def calculate_tax(income: float, province: Province) -> TaxResult:
    """Calculate combined federal and provincial tax for a given income."""
    federal_tax = calculate_bracket_tax(income, FEDERAL_BRACKETS)
    provincial_tax = calculate_bracket_tax(income, PROVINCIAL_BRACKETS[province])

    total_tax = federal_tax + provincial_tax
    effective_rate = total_tax / income if income > 0 else 0.0

    marginal_federal = get_marginal_rate(income, FEDERAL_BRACKETS)
    marginal_provincial = get_marginal_rate(income, PROVINCIAL_BRACKETS[province])

    return TaxResult(
        gross_income=income,
        federal_tax=federal_tax,
        provincial_tax=provincial_tax,
        total_tax=round(total_tax, 2),
        effective_rate=round(effective_rate, 4),
        marginal_rate_federal=marginal_federal,
        marginal_rate_provincial=marginal_provincial,
        marginal_rate_combined=marginal_federal + marginal_provincial,
        after_tax_income=round(income - total_tax, 2),
    )


def calculate_rrsp_tax_savings(
    income: float, contribution: float, province: Province
) -> float:
    """Calculate tax savings from an RRSP contribution.

    The savings is the difference in tax between the original income
    and income minus the RRSP contribution.
    """
    tax_without = calculate_tax(income, province).total_tax
    tax_with = calculate_tax(income - contribution, province).total_tax
    return round(tax_without - tax_with, 2)


def calculate_rrsp_contribution_room(
    earned_income: float, pension_adjustment: float = 0.0
) -> float:
    """Calculate new RRSP contribution room for a given year.

    Room = 18% of prior year earned income - pension adjustment,
    capped at the annual maximum.
    """
    room = earned_income * RRSP_CONTRIBUTION_RATE - pension_adjustment
    return min(max(room, 0), RRSP_MAX_CONTRIBUTION_2025)


def calculate_withdrawal_tax(amount: float, province: Province) -> float:
    """Calculate withholding tax on an RRSP withdrawal."""
    if province == Province.QUEBEC:
        rates = WITHDRAWAL_TAX_RATES_QUEBEC
    else:
        rates = WITHDRAWAL_TAX_RATES

    for threshold, rate in rates:
        if amount <= threshold:
            return round(amount * rate, 2)

    return round(amount * rates[-1][1], 2)
