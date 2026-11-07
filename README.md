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

```sql
UPDATE rooms
SET
    room_name = CASE room_code
        WHEN 'standard-1' THEN 'Standard Comfort Room'
        WHEN 'standard-2' THEN 'Window View Room'
        WHEN 'standard-3' THEN 'Spacious Standard Room'
        WHEN 'standard-4' THEN 'Standard City Room'
        WHEN 'deluxe-1' THEN 'Mordern Deluxe Room'
        WHEN 'deluxe-2' THEN 'Deluxe Comfort Room'
        WHEN 'deluxe-3' THEN 'Spacious Deluxe Room'
        WHEN 'deluxe-4' THEN 'Deluxe Premium Room'
        WHEN 'executive-1' THEN 'Executive Premium Suite'
        WHEN 'executive-2' THEN 'Executive City Suite'
        WHEN 'executive-3' THEN 'Executive Lounge Suite'
        WHEN 'executive-4' THEN 'Executive Business Suite'
        WHEN 'family-1' THEN 'Family Comfort Room'
        WHEN 'family-2' THEN 'Family Mordern Room'
        WHEN 'family-3' THEN 'Family Premium Suite'
        WHEN 'family-4' THEN 'Family Grand Suite'
    END,
    category = CASE room_code
        WHEN 'standard-1' THEN 'standard'
        WHEN 'standard-2' THEN 'standard'
        WHEN 'standard-3' THEN 'standard'
        WHEN 'standard-4' THEN 'standard'
        WHEN 'deluxe-1' THEN 'deluxe'
        WHEN 'deluxe-2' THEN 'deluxe'
        WHEN 'deluxe-3' THEN 'deluxe'
        WHEN 'deluxe-4' THEN 'deluxe'
        WHEN 'executive-1' THEN 'executive'
        WHEN 'executive-2' THEN 'executive'
        WHEN 'executive-3' THEN 'executive'
        WHEN 'executive-4' THEN 'executive'
        WHEN 'family-1' THEN 'family'
        WHEN 'family-2' THEN 'family'
        WHEN 'family-3' THEN 'family'
        WHEN 'family-4' THEN 'family'
    END,
    price_per_night = CASE room_code
        WHEN 'standard-1' THEN 2500
        WHEN 'standard-2' THEN 3000
        WHEN 'standard-3' THEN 3500
        WHEN 'standard-4' THEN 4000
        WHEN 'deluxe-1' THEN 4500
        WHEN 'deluxe-2' THEN 5000
        WHEN 'deluxe-3' THEN 5500
        WHEN 'deluxe-4' THEN 6000
        WHEN 'executive-1' THEN 6500
        WHEN 'executive-2' THEN 7000
        WHEN 'executive-3' THEN 7500
        WHEN 'executive-4' THEN 8000
        WHEN 'family-1' THEN 8500
        WHEN 'family-2' THEN 9000
        WHEN 'family-3' THEN 9500
        WHEN 'family-4' THEN 10000
    END,
    description = CASE room_code
        WHEN 'standard-1' THEN 'A comfortable and affordable room designed for a relaxing stay.'
        WHEN 'standard-2' THEN 'Enjoy a peaceful stay in this window-facing room with a welcoming atmosphere.'
        WHEN 'standard-3' THEN 'A spacious room ideal for friends or guests travelling together.'
        WHEN 'standard-4' THEN 'A comfortable city room offering convenience for business and leisure travellers.'
        WHEN 'deluxe-1' THEN 'A beautifully furnished deluxe room with a relaxing garden atmosphere.'
        WHEN 'deluxe-2' THEN 'Experience extra comfort in this elegant deluxe room with a spacious king bed.'
        WHEN 'deluxe-3' THEN 'A spacious deluxe  room offering comfortable accommodation for families or friends.'
        WHEN 'deluxe-4' THEN 'Enjoy an upgraded stay in this premium deluxe room with elegant furnishings.'
        WHEN 'executive-1' THEN 'An executive suite designed for business travellers seeking comfort and outdoor experience.'
        WHEN 'executive-2' THEN 'A stylish executive suite offering a comfortable stay with city-inspired surroundings.'
        WHEN 'executive-3' THEN 'An elegant executive suite designed for guests who appreciate extra comfort and relaxation.'
        WHEN 'executive-4' THEN 'A premium executive suite offering spacious accommodation and nice looking premium room.'
        WHEN 'family-1' THEN 'A welcoming family room offering comfortable accommodation for the whole family.'
        WHEN 'family-2' THEN 'A relaxing mordern looking room with a touch of simplicity but highly comfortable.'
        WHEN 'family-3' THEN 'A spacious connecting suite designed to accommodate families travelling together.'
        WHEN 'family-4' THEN 'A grand family suite offering generous space and flexible sleeping arrangements.'
    END,
    bed_type = CASE room_code
        WHEN 'standard-1' THEN '1 Queen Bed'
        WHEN 'standard-2' THEN '1 Queen Bed'
        WHEN 'standard-3' THEN '1 Queen Bed'
        WHEN 'standard-4' THEN '1 Queen Bed'
        WHEN 'deluxe-1' THEN '1 Queen Bed'
        WHEN 'deluxe-2' THEN '1 King Bed'
        WHEN 'deluxe-3' THEN '1 Queen Beds'
        WHEN 'deluxe-4' THEN '1 King Bed'
        WHEN 'executive-1' THEN '1 King Bed'
        WHEN 'executive-2' THEN '1 King Bed'
        WHEN 'executive-3' THEN '1 King Bed'
        WHEN 'executive-4' THEN '1 King Bed'
        WHEN 'family-1' THEN '2 Single Beds'
        WHEN 'family-2' THEN '2 Queen Beds'
        WHEN 'family-3' THEN '2 King Beds'
        WHEN 'family-4' THEN '2 Queen Beds and 1 Sofa Bed'
    END,
    room_details = CASE room_code
        WHEN 'standard-1' THEN 'A practical and comfortable room featuring a double bed, private bathroom, wardrobe, bedside table and seating area. Suitable for individual guests and couples.'
        WHEN 'standard-2' THEN 'A peaceful room featuring a queen bed, a nice window to view a nice beautiful garden.'
        WHEN 'standard-3' THEN 'A room with queen bed, private bathroom, wardrobe, bedside tables and a comfortable layout for two guests.'
        WHEN 'standard-4' THEN 'A convenient city room featuring a queen bed, private bathroom, wardrobe, work-friendly space and comfortable furnishings.'
        WHEN 'deluxe-1' THEN 'An upgraded room featuring a queen bed, comfortable furnishings, private bathroom, wardrobe and a relaxing garden atmosphere.'
        WHEN 'deluxe-2' THEN 'A spacious deluxe room featuring a king bed, elegant furnishings, private bathroom, wardrobe and additional relaxation space.'
        WHEN 'deluxe-3' THEN 'A generously arranged room with queen bed, private bathroom, wardrobe and comfortable space for guests travelling together.'
        WHEN 'deluxe-4' THEN 'A premium room featuring a king bed, stylish interior finishes, private bathroom, wardrobe and a relaxing seating area.'
        WHEN 'executive-1' THEN 'A business-friendly suite featuring a king bed, comfortable work area, private bathroom, wardrobe and spacious interior.'
        WHEN 'executive-2' THEN 'An elegant suite featuring a king bed, comfortable seating, private bathroom, wardrobe and a relaxing city-style atmosphere.'
        WHEN 'executive-3' THEN 'A refined suite featuring a king bed, spacious seating area, private bathroom and comfortable furnishings for relaxation.'
        WHEN 'executive-4' THEN 'A spacious premium suite featuring a king bed, sofa bed, private bathroom, wardrobe, comfortable seating and additional living space.'
        WHEN 'family-1' THEN 'A family-friendly room featuring one queen bed and two single beds, private bathroom, wardrobe and enough sleeping space for family members.'
        WHEN 'family-2' THEN 'A spacious mordern family room featuring two queen beds, private bathroom, wardrobe and comfortable furnishings.'
        WHEN 'family-3' THEN 'A connecting family suite featuring one king bed and two single beds, private bathroom and generous space for family accommodation.'
        WHEN 'family-4' THEN 'A grand family suite featuring two queen beds and a sofa bed, private bathroom, wardrobe and generous living space.'
    END,
    amenities = CASE room_code
        WHEN 'standard-1' THEN 'Comfortable bed, Private bathroom, Wardrobe, Bedside table, Seating area'
        WHEN 'standard-2' THEN 'Queen bed, Private bathroom, Wardrobe, Garden view, Seating area'
        WHEN 'standard-3' THEN 'Queen bed, Private bathroom, Wardrobe, Bedside tables, Seating area'
        WHEN 'standard-4' THEN 'Queen bed, Private bathroom, Wardrobe, Work area, Seating area'
        WHEN 'deluxe-1' THEN 'Queen bed, Private bathroom, Wardrobe, Comfortable seating'
        WHEN 'deluxe-2' THEN 'King bed, Private bathroom, Spacious interior, Wardrobe, Seating area'
        WHEN 'deluxe-3' THEN 'Queen bed, Private bathroom, Wardrobe, Spacious accommodation'
        WHEN 'deluxe-4' THEN 'King bed, Premium furnishings, Private bathroom, Wardrobe, Seating area'
        WHEN 'executive-1' THEN 'King bed, Work area, Private bathroom, Wardrobe, Comfortable seating'
        WHEN 'executive-2' THEN 'King bed, City surroundings, Private bathroom, Wardrobe, Seating area'
        WHEN 'executive-3' THEN 'King bed, Lounge area, Private bathroom, Wardrobe, Premium furnishings'
        WHEN 'executive-4' THEN 'King bed, Sofa bed, Private bathroom, Lounge area, Wardrobe, Spacious interior'
        WHEN 'family-1' THEN 'Two single beds, Private bathroom, Wardrobe, Family accommodation'
        WHEN 'family-2' THEN 'Two queen beds, Private bathroom, Wardrobe, Family accommodation'
        WHEN 'family-3' THEN 'Two King beds, Private bathroom, Connecting layout, Wardrobe'
        WHEN 'family-4' THEN 'Two queen beds, Sofa bed, Private bathroom, Spacious living area, Wardrobe'
    END
WHERE room_code IN (
    'standard-1', 'standard-2', 'standard-3', 'standard-4',
    'deluxe-1', 'deluxe-2', 'deluxe-3', 'deluxe-4',
    'executive-1', 'executive-2', 'executive-3', 'executive-4',
    'family-1', 'family-2', 'family-3', 'family-4'
);
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

## Update food menu prices

Update the `price` column for these existing `food_menu` items. Replace
`0.00` with the desired price before running the statements:

```sql
UPDATE food_menu
SET price = 700.00
WHERE food_name = 'Vegetable Pizza';

UPDATE food_menu
SET price = 450.00
WHERE food_name = 'Chicken Curry and Rice';
```

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
