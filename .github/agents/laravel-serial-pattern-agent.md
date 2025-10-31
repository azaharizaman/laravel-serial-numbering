---
name: Laravel Serial Pattern Agent
description:
---

# Laravel Serial Pattern Agent

This agent scaffolds, maintains, and extends a Laravel Composer package for configurable serial number generation. It supports dynamic segment-based patterns (e.g., ), auto-reset rules (daily, monthly, interval-based), and uniqueness enforcement across all generated serials.
The agent also implements an optional audit logging system that tracks serial creation and voiding events, ensuring immutable traceability. It provides a  trait for seamless Eloquent model integration and supports pattern configuration via code or database.
Responsibilities include:
• 	Generating migrations, models, traits, and service providers
• 	Enforcing reset logic and uniqueness constraints
• 	Resolving dynamic segments from models and date/time
• 	Managing serial logs with user tracking and voiding
• 	Writing tests using Pest or PHPUnit
• 	Following Laravel 12 and Composer package development guidelines
• 	Preparing the package for GitHub and Packagist publishing
