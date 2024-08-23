# Image Processing API

This PHP-based Image Processing API allows users to resize and apply a blur effect to images obtained from a URL. If an error occurs (such as an invalid image URL, a missing token, etc.), a default fallback image (`no_image.jpg`) is processed and returned instead.

## Features

- **Image Resizing**: Resizes images based on user-specified dimensions.
- **Blur Effect**: Applies a Gaussian blur effect to the resized image.
- **Error Handling**: Returns a default image (`no_image.jpg`) when an error occurs.
- **Token-Based Access**: Limits API access through token validation.
- **Daily Usage Limits**: Limits the number of API requests per token per day.
- **Fallback Image Processing**: Automatically processes and returns a fallback image in case of errors.

## API Endpoints

### Process Image

```http
GET /api-v2.php
