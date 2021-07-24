# PiggyMinions
PiggyMinions is a WIP plugin implementing minions for PMMP.

## ⚠️NOTICE⚠️
This plugin is **NO LONGER IN DEVELOPMENT** and has been abandoned. **Do not expect any support.**

## Obtaining Minions
Currently there are no built in commands for giving yourself minions. Instead, use this /give command template, with ActionType being 0 for mining or 1 for farming & target id being the block id:
```
/give MCPEPIG skull:3 1 {MinionInformation:{MinionType:{ActionType:0,TargetID:56}}}
```
**Note**: For farming minions, the target id must be the BLOCK and not the item.

## Issue Reporting
* If you experience an unexpected non-crash behavior with PiggyMinions, click [here](https://github.com/DaPigGuy/PiggyMinions/issues/new?assignees=DaPigGuy&labels=bug&template=bug_report.md&title=).
* If you experience a crash in PiggyMinions, click [here](https://github.com/DaPigGuy/PiggyMinions/issues/new?assignees=DaPigGuy&labels=bug&template=crash.md&title=).
* If you would like to suggest a feature to be added to PiggyMinions, click [here](https://github.com/DaPigGuy/PiggyMinions/issues/new?assignees=DaPigGuy&labels=suggestion&template=suggestion.md&title=).
* Do not file any issues related to outdated API version; we will resolve such issues as soon as possible.
* We do not support any spoons of PocketMine-MP. Anything to do with spoons (Issues or PRs) will be ignored.
  * This includes plugins that modify PocketMine-MP's behavior directly, such as TeaSpoon.

## Information
* We do not support any spoons. Anything to do with spoons (Issues or PRs) will be ignored.
* We are using the following virions: [InvMenu](https://github.com/Muqsit/InvMenu).
    * **You MUST use the pre-compiled phar from [Poggit-CI](https://poggit.pmmp.io/ci/DaPigGuy/PiggyMinions/~) instead of GitHub.**
    * If you wish to run it via source, check out [DEVirion](https://github.com/poggit/devirion).

## License
```
   Copyright 2020 DaPigGuy

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

```
