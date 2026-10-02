# TODO - Async Email Queue for Ticket Notifications

## Goal
Convert the ticket submission email notification from **synchronous** `MailService::send()` (blocking the ticket request) to an **asynchronous** queue using the existing `email_queue` table.

## Steps
- [x] 1. Create `src/Services/MailQueueService.php` (enqueue / process / retry)
- [x] 2. Create `src/Controllers/MailQueueController.php` (manual trigger + status)
- [x] 3. Update `TicketService::emailAdmins()` to enqueue instead of sending synchronously
- [x] 4. Register `/mail-queue/process` + `/mail-queue/status` admin routes in `index.php`
- [x] 5. Create `mail_queue_worker.php` CLI script to process the queue
- [x] 6. Create a smoke test to verify enqueue + process
- [x] 7. Run php -l on all modified files and test

## Note
A previous async MailQueue attempt (with browser heartbeat) was reverted because it didn't reliably deliver emails. This implementation uses a dedicated CLI worker + admin-triggered processing, which is more reliable.
