# Starian Hotel

## Set room images

Run these statements in your hotel database to set the image filename for each
room. The rows follow the order shown in the rooms table screenshot. The image
files are in the project's `img` folder; store only each filename in the
`rooms.image` column.

```sql
UPDATE rooms SET image = 'bed.jpg' WHERE room_code = 'standard-1';
UPDATE rooms SET image = 'sbed2.jpg' WHERE room_code = 'standard-2';
UPDATE rooms SET image = 'sbed3.jpg' WHERE room_code = 'standard-3';
UPDATE rooms SET image = 'sbed4.jpg' WHERE room_code = 'standard-4';

UPDATE rooms SET image = 'dbed.jpg' WHERE room_code = 'deluxe-1';
UPDATE rooms SET image = 'dbed2.jpg' WHERE room_code = 'deluxe-2';
UPDATE rooms SET image = 'dbed3.jpg' WHERE room_code = 'deluxe-3';
UPDATE rooms SET image = 'dbed4.jpg' WHERE room_code = 'deluxe-4';

UPDATE rooms SET image = 'ebed.jpg' WHERE room_code = 'executive-1';
UPDATE rooms SET image = 'ebed2.jpg' WHERE room_code = 'executive-2';
UPDATE rooms SET image = 'ebed3.jpg' WHERE room_code = 'executive-3';
UPDATE rooms SET image = 'ebed4.jpg' WHERE room_code = 'executive-4';

UPDATE rooms SET image = 'fbed.jpg' WHERE room_code = 'family-1';
UPDATE rooms SET image = 'fbed2.jpg' WHERE room_code = 'family-2';
UPDATE rooms SET image = 'fbed3.png' WHERE room_code = 'family-3';
UPDATE rooms SET image = 'fbed4.jpg' WHERE room_code = 'family-4';
```

## User avatars

Add an `avatar` column to `users`. The default below is one of the files in
`img/avatars`; store only the filename in the database, not the folder path.

```sql
ALTER TABLE users
ADD COLUMN avatar ENUM(
  '01_male_black_hoodie.png',
  '02_female_braids_yellow_hoodie.png',
  '03_male_blue_hoodie.png',
  '04_female_glasses_pink_top.png',
  '05_male_glasses_green_shirt.png',
  '06_female_afro_purple_top.png',
  '07_male_headphones_black_top.png',
  '08_female_beige_hijab.png',
  '09_female_curly_red_hoodie.png',
  '10_male_cap_blue_jacket.png',
  '11_female_bun_purple_top.png',
  '12_male_yellow_hoodie.png',
  '13_male_sunglasses_black_jacket.png',
  '14_female_cap_white_top.png',
  '15_male_suit_red_tie.png',
  '16_female_green_top.png'
) NOT NULL DEFAULT '01_male_black_hoodie.png';
```

Choose any of these filenames when setting a user's avatar:

```sql
UPDATE users
SET avatar = '02_female_braids_yellow_hoodie.png'
WHERE id = 1;
```

Available avatar filenames:

- `01_male_black_hoodie.png`
- `02_female_braids_yellow_hoodie.png`
- `03_male_blue_hoodie.png`
- `04_female_glasses_pink_top.png`
- `05_male_glasses_green_shirt.png`
- `06_female_afro_purple_top.png`
- `07_male_headphones_black_top.png`
- `08_female_beige_hijab.png`
- `09_female_curly_red_hoodie.png`
- `10_male_cap_blue_jacket.png`
- `11_female_bun_purple_top.png`
- `12_male_yellow_hoodie.png`
- `13_male_sunglasses_black_jacket.png`
- `14_female_cap_white_top.png`
- `15_male_suit_red_tie.png`
- `16_female_green_top.png`

To display a saved avatar in the site, use the `img/avatars/` folder followed
by the stored filename.

## Food order tables

The menu and food checkout use `food_orders` and `food_order_items`. Each
ordered item gets its image from the existing `food_menu.image` column through
its `food_id`; do not store a duplicate image on the order itself. The tables
reference the existing `users.id` and `food_menu.id` columns. Create them after
those existing tables; their referenced ID columns should be `INT`.

```sql
CREATE TABLE IF NOT EXISTS food_orders (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  total_amount DECIMAL(10, 2) NOT NULL,
  order_status ENUM(
    'pending',
    'confirmed',
    'preparing',
    'completed',
    'cancelled'
  ) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_food_orders_user_created (user_id, created_at),
  CONSTRAINT fk_food_orders_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS food_order_items (
  id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  food_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  subtotal DECIMAL(10, 2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_food_order_items_order (order_id),
  KEY idx_food_order_items_food (food_id),
  CONSTRAINT fk_food_order_items_order
    FOREIGN KEY (order_id) REFERENCES food_orders (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_food_order_items_food
    FOREIGN KEY (food_id) REFERENCES food_menu (id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Use this query to retrieve every item in an order with its own menu image:

```sql
SELECT
  food_order_items.order_id,
  food_menu.food_name,
  food_order_items.quantity,
  food_order_items.price,
  food_order_items.subtotal,
  food_menu.image
FROM food_order_items
INNER JOIN food_menu
  ON food_menu.id = food_order_items.food_id
WHERE food_order_items.order_id = ?;
```
