-- =====================================================================
-- Megan's Jewelry POS - schema fixes
-- Run ONCE in phpMyAdmin on your live database (take a backup/export first).
-- =====================================================================

-- 1) Fix orphaned data BEFORE adding constraints -----------------------
-- Sale #1 points at customer 5, which no longer exists.
UPDATE sales SET customer_id = NULL
WHERE customer_id IS NOT NULL
  AND customer_id NOT IN (SELECT id FROM customers);

-- 2) Merge the legacy `sales_items` table into `sale_items` ------------
-- The app only reads sale_items, so sales #9-#12 showed ₱0 / "No items".
-- (Only copies sales that have no rows in sale_items yet, so it is safe to re-run.)
-- Skipped automatically if sales_items was already dropped.
INSERT INTO sale_items (sale_id, product_id, quantity, price)
SELECT x.sale_id, x.product_id, x.quantity, x.price
FROM sales_items x
WHERE x.sale_id NOT IN (SELECT DISTINCT sale_id FROM sale_items);

-- 3) MyISAM -> InnoDB (MyISAM ignores transactions, so a failed checkout
--    could not be rolled back) and latin1 -> utf8mb4 (the app connects as utf8mb4)
ALTER TABLE customers  ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE products   ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE sales      ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE sale_items ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE users      ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 4) Constraints --------------------------------------------------------
-- Invoice numbers must be unique (the old YmdHis format could collide).
ALTER TABLE sales ADD UNIQUE KEY uq_sales_invoice_no (invoice_no);

-- Never allow negative stock.
-- (If your MySQL/MariaDB version ignores CHECK, the app-level guard in pos.php still protects you.)
ALTER TABLE products ADD CONSTRAINT chk_products_stock CHECK (stock >= 0);

ALTER TABLE sales
  ADD CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;
ALTER TABLE sale_items
  ADD CONSTRAINT fk_items_sale    FOREIGN KEY (sale_id)    REFERENCES sales(id)    ON DELETE CASCADE,
  ADD CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT;

-- 5) Once you have confirmed sales #9-#12 now show their items, remove the duplicate table:
-- DROP TABLE sales_items;
