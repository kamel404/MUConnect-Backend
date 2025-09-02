# AI Queue System Setup

This document explains how to set up and use the new queued AI content generation system.

## Overview

The AI content generation (quizzes and summaries) has been moved to a queued system to prevent timeouts and improve performance under load. Instead of generating content synchronously, requests are now queued and processed asynchronously.

## Database Setup

The following tables have been created:
- `jobs` - Stores queued jobs
- `job_batches` - Stores job batch information
- `failed_jobs` - Stores failed job information
- `ai_contents` - Stores AI-generated content and processing status

## Queue Configuration

The system uses database queues by default. Make sure your `.env` file has:

```env
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=sqlite  # or your database connection
```

## Running the Queue Worker

To process queued jobs, run one of the following commands:

### Option 1: Using the custom command
```bash
php artisan app:process-queue
```

### Option 2: Using Laravel's built-in command
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Option 3: For production (background processing)
```bash
php artisan queue:work --daemon --tries=3 --timeout=120 --sleep=3 --max-jobs=1000
```

## API Endpoints

### Generate Quiz (Async)
```
GET /api/resources/{resourceId}/generate-quiz?question_count=10&difficulty=medium&attachment_id=123
```
**Response:**
```json
{
  "message": "Quiz generation started. Check status using the content ID.",
  "content_id": 1,
  "status": "processing"
}
```

### Generate Summary (Async)
```
GET /api/resources/{resourceId}/generate-summary?summary_type=concise&max_words=300&attachment_id=123
```
**Response:**
```json
{
  "message": "Summary generation started. Check status using the content ID.",
  "content_id": 2,
  "status": "processing"
}
```

### Check Content Status
```
GET /api/ai-content/{contentId}/status
```
**Response:**
```json
{
  "content_id": 1,
  "type": "quiz",
  "status": "completed",
  "content": { ... },
  "completed_at": "2025-09-02T10:30:00Z",
  "resource": { "id": 123, "title": "Sample Resource" },
  "attachment": { "id": 456, "original_name": "document.pdf" }
}
```

### Batch Operations
```
POST /api/resources/{resourceId}/ai-batch
Content-Type: application/json

{
  "operations": [
    {
      "type": "quiz",
      "attachment_id": 123,
      "parameters": {
        "question_count": 10,
        "difficulty": "medium"
      }
    },
    {
      "type": "summary",
      "attachment_id": 456,
      "parameters": {
        "summary_type": "concise",
        "max_words": 300
      }
    }
  ]
}
```

### Check Batch Status
```
GET /api/ai-batch/{batchId}/status
```
**Response:**
```json
{
  "batch_id": "batch_123",
  "total_jobs": 2,
  "pending_jobs": 0,
  "failed_jobs": 0,
  "finished_at": "2025-09-02T10:35:00Z",
  "progress": 100
}
```

## Job Processing Details

### GenerateQuizJob
- **Retries:** 3 times with exponential backoff (10s, 30s, 60s)
- **Timeout:** 120 seconds
- **Queue:** default

### GenerateSummaryJob
- **Retries:** 3 times with exponential backoff (10s, 30s, 60s)
- **Timeout:** 120 seconds
- **Queue:** default

## Monitoring

### Check Queue Status
```bash
php artisan queue:status
```

### List Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

### Clear Failed Jobs
```bash
php artisan queue:clear
```

## Production Deployment

1. **Start queue workers** on your server:
   ```bash
   # Using supervisor or similar process manager
   php artisan queue:work --daemon --tries=3 --timeout=120 --sleep=3
   ```

2. **Monitor queue health**:
   - Check queue size: `php artisan queue:status`
   - Monitor failed jobs: `php artisan queue:failed`
   - Set up alerts for failed jobs

3. **Scale workers** based on load:
   - Multiple workers can run simultaneously
   - Use different queues for different priorities if needed

## Error Handling

- Jobs automatically retry up to 3 times
- Failed jobs are logged to `failed_jobs` table
- Use `php artisan queue:retry` to retry failed jobs
- Check application logs for detailed error information

## Performance Benefits

1. **No more timeouts** - Heavy AI operations don't block web requests
2. **Better concurrency** - Multiple users can request AI generation simultaneously
3. **Resource management** - AI processing happens in background workers
4. **Scalability** - Easy to add more workers as load increases
5. **Reliability** - Failed operations can be retried automatically
