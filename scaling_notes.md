# Scaling & Optimization Notes for Exam Portal

This document outlines potential future improvements to handle a large number of concurrent users.

## 1. Activity Log Scalability

The current system writes to the `activity_logs` table in real-time for every proctoring event (e.g., tab switch, fullscreen exit). This is robust but may not scale to thousands of concurrent users, as it could lead to a high volume of database writes.

### Optimization Strategies:

#### Strategy A: Client-Side Batching (Easy)

- **Concept:** Instead of sending every log event instantly, the JavaScript on the `take.php` page should buffer events locally.
- **Implementation:** Modify the `flushLogsToServer` function in the exam script. Instead of calling it on every event, call it on a timer (e.g., every 60 seconds) or when the buffer reaches a certain size (e.g., 10-15 events).
- **Benefit:** This is the easiest win. It requires no backend changes and can reduce server load by over 90%.

#### Strategy B: Server-Side Queueing (Medium)

- **Concept:** Make the API endpoint extremely fast by offloading the database write.
- **Implementation:**
    1.  When the `LogController` receives a request, instead of writing to MySQL, it writes the log data to a fast, in-memory store like **Redis** or even a simple log file (`/storage/logs/activity.log`).
    2.  Create a separate background script (a "worker" or a cron job) that runs every minute.
    3.  This worker reads the logs from Redis/the file and performs a large, single batch `INSERT` into the MySQL `activity_logs` table.
- **Benefit:** The user's API request is completed in milliseconds, and the main database is never overwhelmed by sudden spikes in activity.

#### Strategy C: Dedicated Logging Database (Advanced)

- **Concept:** Use the right tool for the job. Relational databases (MySQL) are not optimized for high-volume, write-heavy log data.
- **Implementation:** Set up a NoSQL database like **MongoDB** or **Elasticsearch**. Modify the `LogController` (or the worker from Strategy B) to write directly to this database instead of MySQL.
- **Benefit:** This is the most scalable, enterprise-grade solution for handling massive amounts of log data, and it completely separates the performance of the logging system from the core application database.

---

## 2. Webcam Snapshot Storage

Currently, the plan is to upload snapshots as base64 encoded strings and save them. This can quickly fill up the database or local filesystem.

### Optimization Strategy: Cloud Storage

- **Concept:** Offload large file storage to a dedicated service.
- **Implementation:**
    1.  Create an account with a cloud storage provider like **Amazon S3**, **Google Cloud Storage**, or **Cloudinary**.
    2.  Modify the future `SnapshotController` to upload the image file directly to the cloud service instead of saving it locally.
    3.  Store only the **URL** of the image in the database.
- **Benefit:**
    -   Keeps your application server lean and fast.
    -   Infinitely scalable storage.
    -   Often cheaper and more reliable for file hosting.
    -   Allows for easy integration with Content Delivery Networks (CDNs) if needed.
