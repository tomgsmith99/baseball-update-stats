
def ordinal_place(n):
    """
    Convert an integer n into a string like "1st place", "2nd place", etc.
    """
    n = int(n)  # Ensure n is an integer
    # Handle special case for 11, 12, and 13
    if 11 <= (n % 100) <= 13:
        suffix = "th"
    else:
        last_digit = n % 10
        if last_digit == 1:
            suffix = "st"
        elif last_digit == 2:
            suffix = "nd"
        elif last_digit == 3:
            suffix = "rd"
        else:
            suffix = "th"
    return f"{n}{suffix}"
