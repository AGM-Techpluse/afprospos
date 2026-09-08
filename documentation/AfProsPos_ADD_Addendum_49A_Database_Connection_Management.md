# ADD Addendum — §49A. Database Connection Management

**Insert as its own section between existing §49 (Deployment Architecture) and §50 (Operational Resilience).** This follows the same lettered-insertion precedent already used for §21A (Email Provider Architecture) — it slots a new architecture decision in without renumbering everything downstream. Paste the section below directly into the ADD at that point.

Two smaller additions ship with it: one line for the §53 Architecture Review Checklist, one row for the §54 Source Traceability Matrix — both included at the end of this file.

---

## Section to insert

# 49A. Database Connection Management

PHP-FPM does not keep a long-running application process the way Node.js or Java does — each request is served by a worker that boots Laravel fresh, including its MySQL connection, and tears down at the end of the request. There is no in-process memory for an application-level connection pool to live in between requests. This is not a defect to work around inside the application; it is a deployment-layer concern, and it is decided here rather than left to whoever configures the next environment.

```text
Phase 1–8 (current):

PHP-FPM worker ──connect──> MySQL ──close──> (next request repeats)


Trigger condition met (see below):

PHP-FPM worker ──connect──> ProxySQL ──pooled, reused──> MySQL
PHP-FPM worker ──connect──> ProxySQL ──pooled, reused──> MySQL
PHP-FPM worker ──connect──> ProxySQL ──pooled, reused──> MySQL
```

**PDO persistent connections (`PDO::ATTR_PERSISTENT`) are explicitly rejected**, not merely deferred. A persistent connection is reused across requests by the PHP-FPM worker itself, which means a connection can carry an uncommitted transaction, a stale advisory/row lock, or leftover session state from a previous request into the next one. §30 (Transaction and Locking Rules) and the reservation model (BLD §2.5) depend on every request beginning from a clean connection state; that guarantee is worth more than the connection-setup cost it would save.

**ProxySQL is the designated pooling layer, introduced on a specific trigger, not a vague "at scale."** Add it when either is observed in production monitoring (§42 Observability):

```text
[ ] MySQL Threads_connected regularly exceeds 70% of max_connections
[ ] Connection setup (not query execution) is a measurable share of request latency
```

Until then, standard per-request connections via PHP-FPM are correct and sufficient — introducing a pooling layer before there is a measured need adds an operational dependency with nothing to show for it.

ProxySQL is wire-protocol compatible with MySQL, so activating it is a configuration change, not an application change: point `DB_HOST`/`DB_PORT` at ProxySQL instead of MySQL directly.

```text
# Before
DB_HOST=mysql
DB_PORT=3306

# After ProxySQL is introduced
DB_HOST=proxysql
DB_PORT=6033
```

No business module, Repository, or Eloquent Record changes when this happens — the entire pooling layer sits below `config/database.php`.

**Laravel Octane** (Swoole/RoadRunner) is a related but materially larger decision, deliberately out of scope here: it keeps the whole application resident across requests, not just the DB connection, which means every container singleton and per-request binding — including `ActorContext` (§34/§35) — needs an explicit audit for cross-request state leakage before adoption. Do not adopt Octane as a side effect of a connection-pooling conversation; it earns its own architecture review pass if and when it comes up.

Do not enable `PDO::ATTR_PERSISTENT` as a quick fix if connection overhead becomes visible before the ProxySQL trigger is met — introduce ProxySQL early instead, even below the stated thresholds. The failure mode of a leaked lock is a data-integrity incident; the failure mode of "introduced pooling a bit early" is nothing.

---

## Addition to §53 Architecture Review Checklist

Add to the checklist block:

```text
[ ] PDO persistent connections are not enabled anywhere in config/database.php
```

## Addition to §54 Source Traceability Matrix

Add this row:

| Architecture decision | Source |
|---|---|
| Per-request MySQL connections; PDO persistent connections rejected; ProxySQL as the designated scaling lever | Architecture decision (§49A) — informed by §30 Transaction and Locking Rules and NFR Performance/Scalability; no direct BRD/BLD requirement |
