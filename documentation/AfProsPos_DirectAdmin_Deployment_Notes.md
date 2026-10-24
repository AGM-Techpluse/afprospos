# AfProsPos — DirectAdmin SSH Deployment Notes

Practical notes for working in the DirectAdmin SSH terminal on the production/staging server — not binding architecture (see CPNC/ADD/DBDD for that), just things that have bitten us once and are worth not re-discovering.

## PHP CLI version mismatch

**Symptom:** DirectAdmin's "Select PHP Version" panel shows e.g. PHP 8.4.1 for the domain, but running `php -v` in the SSH terminal reports an older version (e.g. 8.1.28), and `composer`/`artisan` commands fail with a "requires PHP >= 8.4.1" platform error.

**Why:** DirectAdmin's PHP Version Selector sets which PHP-FPM handler serves actual web requests for the domain. That's a separate setting from which `php` binary your SSH shell's `PATH` resolves to — the shell keeps defaulting to the server's base/system PHP unless told otherwise.

**1. Find the installed PHP version binaries:**

```sh
ls -d /usr/local/php*/bin/php 2>/dev/null
```

This typically lists something like `/usr/local/php81/bin/php` and `/usr/local/php84/bin/php`. Confirm the one you want:

```sh
/usr/local/php84/bin/php -v
```

If that path doesn't exist, list what's actually installed first:

```sh
ls /usr/local/ | grep -i php
```

**2. Use a specific version directly** for a one-off command, without changing anything permanent:

```sh
/usr/local/php84/bin/php artisan migrate
```

**3. Make it your SSH shell's default `php`** (no root needed — this only affects your own account's sessions):

```sh
echo 'alias php=/usr/local/php84/bin/php' >> ~/.bash_profile
source ~/.bash_profile
php -v
```

If `~/.bash_profile` doesn't exist or doesn't get sourced on login, use `~/.bashrc` instead — check which one already has content.

## Syncing the server's git checkout with GitHub

**Symptom:** `git pull` (or `git fetch` + merge) on the server reports "Already up to date" even though the working-tree files are visibly stale/old.

**Likely cause:** the server's `.git` folder didn't originate from a `git clone` of the GitHub remote — it was copied in (e.g. via a zip upload of a local machine's already-pushed repo). The copied `.git`'s `origin/<branch>` ref already points at whatever commit the local machine was at when it was zipped, so a plain `pull` has nothing new to compare against relative to that inherited ref.

**Fix — force the server to actually resync with GitHub:**

```sh
git fetch origin
git reset --hard origin/<branch-name>
```

`.env` (and anything else gitignored) is untouched by this — `reset --hard` only affects files git is already tracking. Before running it, you can confirm `.env` was never accidentally committed:

```sh
git ls-files | grep -i '^\.env$'
```

If that prints nothing, you're safe to run the reset. If it does print `.env`, back it up first (`cp .env .env.backup-manual`) before resetting.

Going forward, prefer `git clone` directly on the server (or a true `git pull` against a cleanly-cloned checkout) over zip-uploading a `.git` folder, to avoid this class of mismatch.

## Building the frontend on the server

`public/build` is gitignored, so a `git pull`/`reset --hard` deploy never brings the compiled frontend assets with it — something still has to run `npm run build` (or you upload `public/build` some other way; see below).

### Getting Node/npm on a shared-hosting account (no root)

Check DirectAdmin's own panel first — some installs (especially on CloudLinux) have a "Node.js Selector" under the user panel, separate from SSH, which is less fiddly than the below if it's available. Otherwise, use `nvm` (installs entirely in your home directory, no root needed):

```sh
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc   # or ~/.bash_profile — reload so the `nvm` command is available
nvm install --lts
nvm use --lts
```

Then, same as local dev:

```sh
npm install
npm run build
```

### Rolldown thread-pool panic on `npm run build`

**Symptom:**

```
Rolldown panicked. This is a bug in Rolldown, not your code.
thread 'rolldown-worker' panicked ...: IOError(Os { code: 11, kind: WouldBlock, message: "Resource temporarily unavailable" })
...
error during build:
[Error: Panic in async function] { code: 'GenericFailure' }
```

**Why:** Vite 8's bundler (Rolldown, Rust-based) sizes its internal worker-thread pool to the server's *total* CPU core count. Shared-hosting accounts (DirectAdmin/CloudLinux especially) cap how many processes/threads a single account may spawn at once — Rolldown blows past that cap and the OS refuses thread creation with `EAGAIN` (`code: 11`), which crashes the whole build.

**Fix — cap the thread pool to something the account's limit actually allows:**

```sh
RAYON_NUM_THREADS=2 npm run build
```

Confirmed working on this host with `RAYON_NUM_THREADS=2`. To stop having to type it every time:

```sh
echo 'export RAYON_NUM_THREADS=2' >> ~/.bashrc
source ~/.bashrc
```

**If raising/lowering that number still doesn't help:** building on this box isn't a great fit for a tight shared-hosting plan. The more reliable path is to build locally (where none of this applies) and upload only the resulting `public/build/` folder to the server over SFTP/SCP/rsync — the server then never needs Node or Vite installed at all.
