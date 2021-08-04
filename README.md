<a href="https://tiny.cc/JaxksDC"><img src="https://discordapp.com/api/guilds/554059221847638040/embed.png" alt="Discord server"/></a> <a href="https://poggit.pmmp.io/p/Bounty"><img src="https://poggit.pmmp.io/shield.state/Bounty"></a> <a href="https://poggit.pmmp.io/p/Bounty"><img src="https://poggit.pmmp.io/shield.dl.total/Bounty"></a>

# Bounty

A highly customized plugin to bring bounty's to PMMP (only pmmp, no forks/spoons)
Made by JaxkDev, thank you to the community in the above discord for providing soo many suggestions !

### Usage:
- /bounty help/ver/credits
- /bounty new/add name amount
- /bounty list page

### Events:
Only bother looking here if you know what you're doing :)

All events are cancellable and extend BountyEvent.
- BountyAddEvent
- BountyClaimEvent
- BountyCreateEvent
- BountyRemoveEvent

### API:
API Functions:
- Main->hasBounty(string $username): bool
- Main->getBounty(string $username): int
- Main->setBounty(string $username, int $amount): void
