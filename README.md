# Rate This Post

"Rate This Post" is a WordPress plugin that enables a simple voting system on your website, allowing visitors to vote "Yes" or "No" on individual posts. It's designed to engage users and gather feedback on your content directly within your WordPress site.

## Features

- **Simple Voting System**: Users can vote "Yes" or "No" on articles, providing immediate feedback on your content.
- **Security**: Implements WordPress nonces for secure AJAX calls, ensuring that votes are submitted legitimately.
- **AJAX-powered**: Votes are submitted asynchronously, without needing to reload the page, for a seamless user experience.
- **Feedback Visualization**: After voting, users see the current vote tallies, showing community consensus.
- **Prevention of Duplicate Votes**: Tracks user votes using their IP address to prevent multiple votes on the same post by the same user.
- **Admin Insights**: In the WordPress admin area, see how many votes each post has received directly in the post edit screen.
- **Delete Data on Uninstallation**: When plugin is deleted or uninstalled from WordPress, all the plugin data from the database will be deleted.

## Installation

1. Find the plugin folder `wp-app/wp-content/plugins/rate-this-post/`.
2. Upload the entire `rate-this-post` folder to the `/wp-content/plugins/` directory of your WordPress installation.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Once activated, the voting buttons will automatically appear on all posts.

* If you want to use this repo as your development environment, please refer to [WPDC - WordPress Docker Compose](https://github.com/CryptoManiaks/senior-wp-assestment) documentation.

## Usage

After installation, the plugin works out of the box. Each post will display "Yes" and "No" voting buttons at the bottom of the content. Users can click on these to vote on the post.

- **Voting**: Simply click "Yes" or "No" to submit your feedback on a post.
- **Viewing Results**: After voting, the vote counts are updated in real-time for the user to see.

## Customization

While "Rate This Post" is designed to work immediately upon activation, theme developers can customize the appearance of the voting buttons and results display using CSS.

## Screenshots

**Before user vote**

![Voting form after every post](https://i.ibb.co/pxrvQ4d/Screenshot-2024-03-05-at-16-14-47.png)

**After user vote**

![Voting form after every post](https://i.ibb.co/SXwBFJc/Screenshot-2024-03-05-at-16-25-28.png)

## Frequently Asked Questions

**Q: Can users change their vote?**

A: Currently, votes cannot be changed to keep the voting process simple and straightforward.

**Q: Is it possible to reset votes for a post?**

A: Vote resetting is not available through the WordPress admin interface and requires direct database manipulation.

## License

This project is licensed under the GPL v2 or later.

## Test this plugin

This plugin is developed using [WPDC - WordPress Docker Compose](https://github.com/CryptoManiaks/senior-wp-assestment). For any further information on setting up the development environment, please refer to WPDC documentation.
