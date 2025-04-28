
from owner import Owner

import argparse

##########################

# THIS_SEASON = 2025
##########################
def main():
    parser = argparse.ArgumentParser(description="Update an owner's stats.")
    parser.add_argument("--owner_id", type=int, required=True, help="The ID of the owner to update")
    parser.add_argument("--season", type=int, required=True, help="The current season")

    args = parser.parse_args()

    # Use the player_id value
    print(f"Updating the owner with ID: {args.owner_id}")

    owner = Owner(args.owner_id)

    owner.update_stats(args.season)

if __name__ == "__main__":
    main()
