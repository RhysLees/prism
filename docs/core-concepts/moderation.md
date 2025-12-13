# Moderation

Moderate content by checking content against AI-powered models! Moderation helps you detect potentially harmful or inappropriate content before it reaches your users or models.

## Quick Start

Here's how to check text content with just a few lines of code:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::moderation()
    ->using(Provider::OpenAI)
    ->fromInput('Your text to check goes here')
    ->asModeration();

// Check if any content was flagged
if ($response->isFlagged()) {
    // Handle flagged content
    $flagged = $response->firstFlagged();
}
```

## Checking Multiple Inputs

You can check multiple text inputs at once:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::moderation()
    ->using(Provider::OpenAI)
    // First input
    ->fromInput('First text to check')
    // Second input
    ->fromInput('Second text to check')
    // Multiple inputs at once
    ->fromArray([
        'Third text',
        'Fourth text'
    ])
    ->asModeration();

// Get all flagged results
$flaggedResults = $response->flagged();

foreach ($flaggedResults as $result) {
    // Handle each flagged result
    $categories = $result->categories;
    $scores = $result->categoryScores;
}
```

## Image Moderation

You can also moderate images! This is useful for checking user-uploaded images for inappropriate content:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Media\Image;

$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromImage(Image::fromUrl('https://example.com/image.png'))
    ->asModeration();

if ($response->isFlagged()) {
    // Handle flagged image
}
```

### Mixed Text and Image Moderation

You can check both text and images in a single request:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Media\Image;

$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromInput('Check this text')
    ->fromImage(Image::fromUrl('https://example.com/image.png'))
    ->fromInput('Another text to check')
    ->fromImages([
        Image::fromLocalPath('/path/to/image1.jpg'),
        Image::fromLocalPath('/path/to/image2.jpg'),
    ])
    ->asModeration();

// Each input gets its own result in the response
foreach ($response->results as $index => $result) {
    if ($result->flagged) {
        // Handle flagged content at index $index
    }
}
```

## Input Methods

You've got several convenient ways to feed text into the moderation checker:

### Direct Text Input

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::moderation()
    ->using(Provider::OpenAI)
    ->fromInput('Check this text for moderation')
    ->asModeration();
```

### From File

Need to check a larger document? No problem:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::moderation()
    ->using(Provider::OpenAI)
    ->fromFile('/path/to/your/document.txt')
    ->asModeration();
```

> [!NOTE]
> Make sure your file exists and is readable. The moderation checker will throw a helpful `PrismException` if there's any issue accessing the file.

### Image Input

Check images from various sources:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\ValueObjects\Media\Image;

// From a URL
$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromImage(Image::fromUrl('https://example.com/image.png'))
    ->asModeration();

// From a local file
$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromImage(Image::fromLocalPath('/path/to/image.jpg'))
    ->asModeration();

// From a storage disk
$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromImage(Image::fromStoragePath('/path/to/image.jpg', 'my-disk'))
    ->asModeration();

// From base64
$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromImage(Image::fromBase64($base64Data, 'image/jpeg'))
    ->asModeration();

// Multiple images at once
$response = Prism::moderation()
    ->using(Provider::OpenAI, 'omni-moderation-latest')
    ->fromImages([
        Image::fromUrl('https://example.com/image1.png'),
        Image::fromLocalPath('/path/to/image2.jpg'),
    ])
    ->asModeration();
```

> [!NOTE]
> Image moderation requires the `omni-moderation-latest` model (or similar image-capable moderation models). Make sure to specify the correct model when using image moderation.

## Response Handling

The moderation response provides everything you need to handle flagged content:

```php
use Prism\Prism\Moderation\Response;
use Prism\Prism\ValueObjects\ModerationResult;

// Check if any content was flagged
if ($response->isFlagged()) {
    // Get the first flagged result
    $firstFlagged = $response->firstFlagged();
    
    // Or get all flagged results
    $allFlagged = $response->flagged();
}

// Access individual results
foreach ($response->results as $result) {
    /** @var ModerationResult $result */
    $isFlagged = $result->flagged;
    $categories = $result->categories; // Array of category => bool
    $categoryScores = $result->categoryScores; // Array of category => float
    $id = $result->id; // Unique identifier for the result
}
```

### Understanding Results

Each moderation result includes:

- **`flagged`**: A boolean indicating if the content was flagged as potentially harmful
- **`categories`**: An array mapping category names to boolean values indicating if that category was detected
- **`categoryScores`**: An array mapping category names to float values indicating the confidence level
- **`id`**: A unique identifier for the moderation check

```php
$result = $response->results[0];

if ($result->flagged) {
    // Check specific categories
    if ($result->categories['hate'] ?? false) {
        // Handle hate content
    }
    
    if ($result->categories['harassment'] ?? false) {
        // Handle harassment
    }
    
    // Check scores for more nuanced handling
    $hateScore = $result->categoryScores['hate'] ?? 0.0;
    if ($hateScore > 0.5) {
        // High confidence of hate content
    }
}
```

## Common Settings

You can fine-tune your moderation requests just like other Prism features:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

$response = Prism::moderation()
    ->using(Provider::OpenAI, 'text-moderation-latest')
    ->fromInput('Your text here')
    ->withClientOptions(['timeout' => 30]) // Adjust request timeout
    ->withClientRetry(3, 100) // Add automatic retries
    ->withProviderOptions([
        // Provider-specific options
    ])
    ->asModeration();
```

## Error Handling

Always handle potential errors gracefully:

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;

try {
    $response = Prism::moderation()
        ->using(Provider::OpenAI)
        ->fromInput('Your text here')
        ->asModeration();
        
    if ($response->isFlagged()) {
        // Handle flagged content
    }
} catch (PrismException $e) {
    Log::error('Moderation check failed:', [
        'error' => $e->getMessage()
    ]);
}
```

## Use Cases

Moderation is useful for:

- **User-Generated Content**: Check comments, posts, or messages before displaying them
- **Content Filtering**: Filter out inappropriate content in chat applications
- **Image Moderation**: Verify user-uploaded images meet platform guidelines
- **Pre-Processing**: Check inputs before sending them to other AI models
- **Compliance**: Ensure content meets platform guidelines and policies
- **Mixed Content**: Check both text and images together in a single request

## Pro Tips

**Thresholds**: Use category scores to implement custom thresholds. Different applications may need different sensitivity levels.

**Batch Processing**: Check multiple inputs in a single request for better performance and efficiency.

**Caching**: Consider caching moderation results for repeated content to reduce API calls.

**Logging**: Always log flagged content for audit trails and to improve your filtering over time.

> [!IMPORTANT]
> Different providers may have different category names and scoring systems. Always check your provider's documentation for specific details about available categories and score interpretations.


