# TODO - Internationalize Customer and Investor Views

## Step 1: Extract hardcoded Arabic text ✅ COMPLETED
- Identify all hardcoded Arabic text in customer views:
  - resources/views/customers/index.blade.php
  - resources/views/customers/create.blade.php
  - resources/views/customers/edit.blade.php
  - resources/views/customers/show.blade.php
  - resources/views/customers/import.blade.php
- Identify all hardcoded Arabic text in investor views (except index.blade.php):
  - resources/views/investors/create.blade.php
  - resources/views/investors/edit.blade.php
  - resources/views/investors/show.blade.php
  - resources/views/investors/import.blade.php
  - resources/views/investors/allliquidity.blade.php

## Step 2: Add translation keys ✅ COMPLETED
- Add new translation keys for extracted Arabic text to:
  - resources/lang/ar.json ✅ COMPLETED
  - resources/lang/en.json ✅ COMPLETED

## Step 3: Replace hardcoded text with translation keys
- Update customer views to use translation keys:
  - resources/views/customers/index.blade.php ✅ COMPLETED
  - resources/views/customers/create.blade.php ✅ COMPLETED
  - resources/views/customers/edit.blade.php
  - resources/views/customers/show.blade.php
  - resources/views/customers/import.blade.php
- Update investor views to use translation keys:
  - resources/views/investors/create.blade.php
  - resources/views/investors/edit.blade.php
  - resources/views/investors/show.blade.php
  - resources/views/investors/import.blade.php
  - resources/views/investors/allliquidity.blade.php

## Step 4: Testing and verification
- Test views to ensure translations render correctly
- Verify proper RTL/LTR text direction handling

---

I will start with Step 1: Extracting hardcoded Arabic text from customer views.
