<a href="https://tiny.cc/JaxksDC"><img src="https://discordapp.com/api/guilds/554059221847638040/embed.png" alt="Discord server"/></a> <a href="https://poggit.pmmp.io/p/Bounty"><img src="https://poggit.pmmp.io/shield.state/Bounty"></a> <a href="https://poggit.pmmp.io/p/Bounty"><img src="https://poggit.pmmp.io/shield.dl.total/Bounty"></a>

# Bounty

A highly customized plugin to bring bounty's to PMMP (only pmmp, no forks/spoons)
Made by Jaxk (aka Jackthehack), thank you to the community in the above discord for providing soo many suggestions !

### Usage:
- /bounty help/ver/credits
- /bounty new/add name amount
- /bounty list page
- /bounty leaderboard/list
- /bounty leaderboard setpos (set position of floating text leaderboard if enabled in config.yml)

### Events:
Only bother looking here if you know what your doing :)

All events are cancellable and extend BountyEvent.
- BountyAddEvent
- BountyClaimEvent
- BountyCreateEvent
- BountyRemoveEvent

### API:
API Functions:
- Main::getInstance()
- Main->hasBounty(string $name): bool
- Main->getBounty(string $nick): int
