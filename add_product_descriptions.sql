-- Add description column to products table
ALTER TABLE products ADD COLUMN product_description TEXT AFTER category;

-- Update product descriptions
UPDATE products SET product_description = 'This Lotus Bracelet features a delicate lotus design that represents inner peace and strength. Its elegant and simple style makes it suitable for everyday use.' WHERE product_name = 'Lotus Bracelet';

UPDATE products SET product_description = 'The Lotus Necklace showcases a beautifully crafted lotus pendant symbolizing purity, hope, and renewal. A meaningful accessory that adds beauty and elegance to any outfit.' WHERE product_name = 'Lotus Necklace';

UPDATE products SET product_description = 'A timeless pearl necklace designed to enhance elegance and sophistication. This classic accessory is perfect for completing both formal and semi-formal looks.' WHERE product_name = 'Pearl Necklace';

UPDATE products SET product_description = 'The Tied Knot Necklace features a refined knot pendant that represents love and connection. It is a stylish piece that can be worn alone or layered with other necklaces.' WHERE product_name = 'Necklace Tied Knot';

UPDATE products SET product_description = 'Inspired by the beauty of the lotus flower, these earrings symbolize purity and growth. Their unique design adds a graceful and meaningful touch to your style.' WHERE product_name = 'Lotus Earrings';

UPDATE products SET product_description = 'These pearl earrings bring a soft and elegant charm to your look. Their simple design makes them a versatile accessory suitable for any occasion.' WHERE product_name = 'Pearl Earrings';

UPDATE products SET product_description = 'Tied Knot Earrings feature a delicate knot design that represents strength and togetherness. Lightweight and stylish, they are perfect for adding a subtle statement to any outfit.' WHERE product_name = 'Tied Knot Earrings';

UPDATE products SET product_description = 'This pearl bracelet offers a timeless and classy look that never goes out of style. Designed to add elegance and sophistication, it pairs well with both formal and casual outfits.' WHERE product_name = 'Pearl Bracelet';

UPDATE products SET product_description = 'A minimalist bracelet featuring a classic tied knot design that symbolizes unity and connection. Its simple yet stylish look makes it perfect for everyday wear or as a meaningful gift.' WHERE product_name = 'Tied Knot Bracelet';
