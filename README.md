# My Elementor Vue Plugin

WordPress plugin with Vue.js and Elementor-style UI.

## Installation

1. Run 
pm install to install dependencies
2. Run 
pm run build to build the Vue app
3. Activate the plugin in WordPress admin

## Development

- Run 
pm run dev for development with hot reload
- Run 
pm run watch to watch for changes

## Features

- Vue.js SPA within WordPress admin
- REST API endpoints for CRUD operations
- Elementor-inspired UI design
- Database table creation on activation
- AJAX and REST API support

## Usage

Access the plugin via WordPress admin menu "Vue Plugin"

## API Endpoints

- GET /wp-json/mevp/v1/data - Get all data
- POST /wp-json/mevp/v1/data/{id} - Create/Update data
- DELETE /wp-json/mevp/v1/data/{id} - Delete data
- GET /wp-json/mevp/v1/settings - Get settings

## Shortcode

Use [my_vue_plugin] shortcode to display the app on frontend.
