# BitRewards Treasury

This software component is a secure storage and management system for Ethereum cryptocurrency and BIT ERC-20 tokens.

It connects the BitRewards Loyalty Platform with the Ethereum public blockchain, allowing to perform these tasks:

- Input of BIT tokens;
- Input of Ethereum cryptocurrency; 
- Output of BIT tokens;
- Output of Ethereum cryptocurrency; 
- Checking the incoming and outcoming transactions statuses;
- Extracting other ERC-20 token transfers from the Ethereum blockchain data; 
- Estimating the ETH fee for BIT token transfer;
- Creating ERC20-compatible crypto-wallets.

This software can be adapted to work with any ERC-20 token. 
It is highly configurable  and allows you to use it in a variety of scenarios. 

# Installation
Copy `docker/.env.example` file to `docker/.env`, adjust the global project environment to your needs.

Copy `console/node/.env.example` to `console/node/.env`, adjust the NodeJS part environment to your needs.

Adjust app configuration in `environments` to your needs.

Run `composer install` in the default working directory of `treasury-web` container to install Composer dependencies.

Use `docker/docker-compose.yml` to launch the software.

# License

This project is licensed under the terms of the [MIT license](./LICENSE).