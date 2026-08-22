# Xophz Local Produce (EDVEX Data Royalty & Farmer's Market)

**Universal EDVEX Data Royalty Engine & Farmer's Market for COMPASS & YouMeOS.**

## Overview
Xophz Local Produce (`xophz-compass-produce`) is the sovereign data encapsulation and monetization engine for the COMPASS ecosystem. It transforms local WordPress Custom Post Types (CPTs) and Spark application telemetry into tokenized, monetizable digital produce.

## Core Features
* **Universal CPT Provider Discovery:** Dynamically detects all active COMPASS and third-party Custom Post Types via `compass_royalty_cpts` and standard WordPress schemas.
* **Sovereign PII Scrubbing:** Strips emails, passwords, IPs, and internal metadata before computing cryptographic Merkle roots.
* **3-Way Smart Contract Splits:** Enforces automated revenue division on Elysium EVM (Originator 80-99.8%, Polos DAO 0.1-15%, Protocol Architect 0.1-5%) with a hard 0.10% floor.
* **Crypto-Shredding Homeostasis:** Purges symmetric encryption keys and data payloads on demand to guarantee full GDPR compliance.
* **Federated $w^4$ Peer Stalls:** Connects independent BlackBOX nodes as merchant stalls in a decentralized data Farmer's Market.

## REST API Endpoints (`/wp-json/xophz-produce/v1`)
* `GET /providers`: List active CPT harvest pipelines.
* `GET /harvest`: Telemetry summary and projected token yields.
* `POST /package`: Bundle and encrypt data into an EDVEX crate.
* `POST /mint-preview`: Preview on-chain split parameters and gas costs.
* `GET /stalls`: Discover federated $w^4$ peer node stalls.
* `POST /dock`: Negotiate peer-to-peer cryptographic handshake.
* `POST /compost`: Execute cryptographic shredding of local datasets.

## License
GPL-2.0+ - Hall of the Gods, Inc.
