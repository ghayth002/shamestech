# Event Manager Application

## Features

- Event management with milestones
- Front-end QR code generation for events
- Content moderation with bad word filtering

## Bad Word Filter API Integration

This application integrates with a bad word filtering API to ensure content moderation in all user-submitted content. The implementation:

1. Uses the PurgoMalum API (free) for demonstration purposes
2. Checks for inappropriate content in event names, categories, milestone names, and statuses
3. Prevents form submission if inappropriate content is detected
4. Provides user feedback through error messages

### Configuration

The application is configured to use a bad word filter API. Set your API key in the `.env` file:

```
BADWORD_FILTER_API_KEY=your_api_key_here
```

For the free PurgoMalum API, no API key is needed. If you switch to a paid service like WebPurify or Sightengine, you'll need to provide the appropriate key.

### How It Works

1. When users submit forms (events or milestones), the content is sent to the bad word filter service
2. The service checks for inappropriate content using external APIs
3. If inappropriate content is detected, the submission is rejected with a specific error message
4. Clean content is processed normally

### Supported APIs

The current implementation supports:

- [PurgoMalum API](https://www.purgomalum.com/) (default, free)

For production use, consider upgrading to one of these premium services:

- [WebPurify](https://www.webpurify.com/)
- [Sightengine](https://sightengine.com/)
- [CleanText](https://cleantext.io/)

## Installation

1. Clone the repository
2. Run `composer install`
3. Configure your database in `.env`
4. Run migrations: `php bin/console doctrine:migrations:migrate`
5. Start the Symfony server: `symfony server:start`

## QR Code Generation

The application also includes QR code generation for events, allowing users to share event details directly through QR codes. 