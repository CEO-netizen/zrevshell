# Zrevshell

Zrevshell is a **PHP reverse shell** designed for **educational and lab testing purposes only**.  
It demonstrates how reverse shells work and allows you to safely test shell execution and socket connections in controlled environments.

## Features
- Advanced reverse shell using `proc_open`.
- Simple fallback method using `shell_exec`.
- Browser-based testing interface for safe experimentation.

## Installation
1. Configure `$host`, `$port`, and `$shell` in `zrevshell.php`.
2. Deploy on a lab server or local PHP environment.
3. Run in CLI to initiate the shell or access via browser to use the test interface.

## Disclaimer
This tool is for **authorized testing only**. Misuse on production systems or unauthorized targets is illegal and entirely the responsibility of the user.