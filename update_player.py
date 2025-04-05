
from player import Player

import argparse

##########################

# THIS_SEASON = 2025
##########################
def main():
    parser = argparse.ArgumentParser(description="Update a player's record")
    parser.add_argument("--player_id", type=int, required=True, help="The ID of the player to update")
    parser.add_argument("--season", type=int, required=True, help="The current season")

    args = parser.parse_args()

    # Use the player_id value
    print(f"Updating player with ID: {args.player_id}")

    player = Player(args.player_id)

    player.update_stats(args.season)

if __name__ == "__main__":
    main()
