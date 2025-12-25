# Granite Docling Integration - Configuration Guide

## Overview

The document-parser-service now supports IBM Granite Docling for advanced PDF parsing with structure preservation.

## Configuration

### Environment Variables

Add these to your `.env` file:

```bash
# Enable/Disable Granite Docling
USE_GRANITE_DOCLING=true

# Ollama Configuration
OLLAMA_URL=http://192.168.88.120:11434
GRANITE_DOCLING_MODEL=ibm/granite-docling:258m

# Performance Tuning
DOCLING_TIMEOUT=60              # API timeout in seconds (default: 60)
DOCLING_IMAGE_RESOLUTION=150    # Image resolution for PDF conversion (default: 150)
```

### Configuration Options

| Variable | Default | Description |
|----------|---------|-------------|
| `USE_GRANITE_DOCLING` | `true` | Enable/disable Granite Docling parsing |
| `OLLAMA_URL` | `http://192.168.88.120:11434` | Ollama API endpoint |
| `GRANITE_DOCLING_MODEL` | `ibm/granite-docling:258m` | Model to use |
| `DOCLING_TIMEOUT` | `60` | API request timeout (seconds) |
| `DOCLING_IMAGE_RESOLUTION` | `150` | PDF-to-image resolution (DPI) |

## Performance Tuning

### Timeout Adjustment

For large documents or slow networks:
```bash
DOCLING_TIMEOUT=120  # 2 minutes
```

For faster processing on local Ollama:
```bash
DOCLING_TIMEOUT=30   # 30 seconds
```

### Image Resolution

Higher resolution = better accuracy but slower processing:
```bash
DOCLING_IMAGE_RESOLUTION=200  # Higher quality
```

Lower resolution = faster processing but less accurate:
```bash
DOCLING_IMAGE_RESOLUTION=100  # Faster processing
```

**Recommended**: 150 DPI (good balance)

## Monitoring

### Log Metrics

The service logs performance metrics:

```json
{
  "message": "PDF extraction completed with Granite Docling",
  "page_count": 2,
  "total_text_length": 5678,
  "total_time": "12.45s",
  "avg_time_per_page": "6.23s",
  "conversion_time": "1.2s"
}
```

### Performance Expectations

| Metric | Typical Value |
|--------|--------------|
| PDF-to-image conversion | 0.5-2s per page |
| Granite Docling processing | 5-15s per page (CPU) |
| Total time (1-page PDF) | 6-17s |
| Total time (5-page PDF) | 30-85s |

## Fallback Behavior

If Granite Docling fails, the service automatically falls back to the basic PDF parser:

```php
// Logs warning and uses fallback
Log::warning('Docling parsing failed, falling back to basic parser');
```

**Fallback triggers:**
- Ollama service unavailable
- Model not loaded
- API timeout
- Image conversion failure

## Memory Optimization

The service includes automatic memory cleanup:
- Clears base64 images after processing each page
- Runs garbage collection after each page
- Clears all images and text arrays after completion

## Troubleshooting

### Issue: Slow Processing

**Solutions:**
1. Reduce image resolution: `DOCLING_IMAGE_RESOLUTION=100`
2. Increase timeout: `DOCLING_TIMEOUT=120`
3. Check Ollama server load
4. Use GPU-accelerated Ollama if available

### Issue: Out of Memory

**Solutions:**
1. Reduce image resolution
2. Process fewer pages concurrently
3. Increase PHP memory limit in `php.ini`

### Issue: Always Using Fallback

**Check:**
1. Ollama service is running: `curl http://192.168.88.120:11434/api/tags`
2. Model is loaded: `ollama list | grep granite-docling`
3. Network connectivity to Ollama server
4. Check logs for specific error messages

## Best Practices

1. **Development**: Use `USE_GRANITE_DOCLING=false` for faster iteration
2. **Staging**: Enable Docling and test with real documents
3. **Production**: Monitor performance metrics and adjust timeouts
4. **Large Documents**: Consider increasing timeout for 10+ page PDFs

## API Response Format

### With Granite Docling

```json
{
  "text": "Extracted text with DocTags markup...",
  "page_count": 2,
  "parser": "granite-docling",
  "model": "ibm/granite-docling:258m"
}
```

### With Basic Parser (Fallback)

```json
{
  "text": "Flattened text...",
  "page_count": 2,
  "parser": "smalot-pdfparser"
}
```

## Example Usage

```php
use App\Services\PdfParserService;

$parser = new PdfParserService();
$result = $parser->extractText('/path/to/resume.pdf');

echo "Parser: " . $result['parser'] . "\n";
echo "Pages: " . $result['page_count'] . "\n";
echo "Text length: " . strlen($result['text']) . "\n";
```

## Metrics Dashboard

Monitor these metrics in your logs:

- **Conversion time**: PDF-to-image conversion duration
- **Processing time per page**: Time to process each page with Docling
- **Total time**: End-to-end processing time
- **Average time per page**: Total time / page count
- **Text length**: Characters extracted
- **Parser used**: Which parser was used (docling vs fallback)
