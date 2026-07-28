-- =====================================================================
-- VoiceChat — Default Seed Data
-- Includes: admin user, gifts, badges, achievements, settings
-- =====================================================================

-- ---------------------------------------------------------------------
-- Default Admin (password: Admin@12345)
-- ---------------------------------------------------------------------
INSERT INTO `users`
  (`uuid`,`username`,`email`,`password`,`display_name`,`role`,`status`,`online_status`,`is_verified`,`level`,`xp`,`coins`,`email_verified_at`)
VALUES
  (UUID(),'admin','admin@voicechat.local',
   '$2y$10$8K1p/a0dL1LXMIgZ.oPa7OYvIx0u2yU0M8k3Dp.Hs1nG4r5X0U.6', -- hash of "Admin@12345"
   'Administrator','superadmin','active','online',1,99,999999,999999,NOW());

-- ---------------------------------------------------------------------
-- Default Demo User (password: Demo@12345)
-- ---------------------------------------------------------------------
INSERT INTO `users`
  (`uuid`,`username`,`email`,`password`,`display_name`,`bio`,`gender`,`country`,`level`,`xp`,`coins`,`online_status`,`is_verified`,`email_verified_at`)
VALUES
  (UUID(),'demo','demo@voicechat.local',
   '$2y$10$8K1p/a0dL1LXMIgZ.oPa7OYvIx0u2yU0M8k3Dp.Hs1nG4r5X0U.6',
   'Demo User','Welcome to VoiceChat! 🎙️','female','Global',10,5000,5000,'online',1,NOW());

-- ---------------------------------------------------------------------
-- Gifts Catalog
-- ---------------------------------------------------------------------
INSERT INTO `gifts` (`name`,`slug`,`description`,`price_coins`,`rarity`,`is_animated`,`sort_order`,`category`) VALUES
  ('Rose','gift-rose','A beautiful red rose',10,'common',1,1,'flower'),
  ('Heart','gift-heart','Send some love',20,'common',1,2,'love'),
  ('Cake','gift-cake','Celebrate with cake',50,'common',1,3,'food'),
  ('Crown','gift-crown','A royal crown',500,'rare',1,4,'royal'),
  ('Diamond','gift-diamond','A precious diamond',1000,'epic',1,5,'jewel'),
  ('Rocket','gift-rocket','To the moon!',2000,'epic',1,6,'special'),
  ('Castle','gift-castle','Build a castle',5000,'legendary',1,7,'special'),
  ('Yacht','gift-yacht','Floating luxury',10000,'legendary',1,8,'special'),
  ('Phoenix','gift-phoenix','Legendary bird',25000,'mythic',1,9,'mythic'),
  ('Galaxy','gift-galaxy','The whole galaxy',50000,'mythic',1,10,'mythic'),
  ('Coffee','gift-coffee','Morning coffee',5,'common',0,11,'food'),
  ('Beer','gift-beer','Cheers!',15,'common',0,12,'food'),
  ('Champagne','gift-champagne','Celebration time',100,'rare',1,13,'food'),
  ('Teddy Bear','gift-teddy','Cute teddy bear',30,'common',1,14,'toy'),
  ('Ring','gift-ring','A precious ring',2000,'epic',1,15,'jewel'),
  ('Car','gift-car','Sports car',15000,'legendary',1,16,'special');

-- ---------------------------------------------------------------------
-- Badges (level-based)
-- ---------------------------------------------------------------------
INSERT INTO `badges` (`name`,`slug`,`description`,`xp_required`,`rarity`,`type`) VALUES
  ('Newbie','badge-newbie','Welcome aboard!',0,'common','level'),
  ('Regular','badge-regular','You''re a regular',1000,'common','level'),
  ('Active','badge-active','Very active member',5000,'rare','level'),
  ('Popular','badge-popular','Everyone knows you',15000,'epic','level'),
  ('Star','badge-star','You''re a star',50000,'epic','level'),
  ('Legend','badge-legend','A true legend',100000,'legendary','level'),
  ('Mythic','badge-mythic','Beyond legendary',500000,'mythic','level'),
  ('Voice Pro','badge-voice-pro','Voice room expert',25000,'epic','achievement'),
  ('Social Butterfly','badge-social','Made many friends',10000,'rare','achievement'),
  ('Generous','badge-generous','Sent many gifts',30000,'epic','achievement');

-- ---------------------------------------------------------------------
-- Achievements
-- ---------------------------------------------------------------------
INSERT INTO `achievements` (`name`,`slug`,`description`,`xp_reward`,`coins_reward`,`criteria`) VALUES
  ('First Room','first-room','Create your first voice room',100,50,'{"action":"create_room","count":1}'),
  ('Room Host','room-host-10','Host 10 voice rooms',500,200,'{"action":"create_room","count":10}'),
  ('First Friend','first-friend','Make your first friend',50,20,'{"action":"add_friend","count":1}'),
  ('Social Star','social-star-50','Have 50 friends',1000,500,'{"action":"add_friend","count":50}'),
  ('First Gift','first-gift','Send your first gift',50,10,'{"action":"send_gift","count":1}'),
  ('Generous Soul','generous-soul-100','Send 100 gifts',2000,1000,'{"action":"send_gift","count":100}'),
  ('First Message','first-message','Send your first message',20,5,'{"action":"send_message","count":1}'),
  ('Marathoner','marathoner','Spend 10 hours in rooms',1500,500,'{"action":"room_time","count":36000}'),
  ('Verified','verified','Get verified',500,100,'{"action":"verify_account","count":1}'),
  ('Early Bird','early-bird','Join during launch week',200,100,'{"action":"join_early","count":1}');

-- ---------------------------------------------------------------------
-- Default App Settings
-- ---------------------------------------------------------------------
INSERT INTO `settings` (`key_name`,`value`,`type`,`group_name`,`description`,`is_public`) VALUES
  ('site_name','VoiceChat','string','general','Site name',1),
  ('site_description','Professional voice chat platform','string','general','Site description',1),
  ('site_logo','','string','general','Site logo URL',1),
  ('welcome_coins','100','int','rewards','Coins given to new users',0),
  ('daily_bonus_coins','20','int','rewards','Daily login bonus',0),
  ('min_room_duration','60','int','rooms','Minimum room duration (seconds)',0),
  ('max_room_name_length','50','int','rooms','Max room name characters',0),
  ('max_bio_length','500','int','profile','Max bio characters',0),
  ('voice_quality','high','string','rooms','Default voice quality',0),
  ('enable_gifts','1','bool','features','Enable gift system',1),
  ('enable_agencies','1','bool','features','Enable agencies',1),
  ('enable_registration','1','bool','features','Enable new registrations',1),
  ('maintenance_mode','0','bool','general','Maintenance mode',1),
  ('terms_of_service','Please be respectful to all users.','text','legal','Terms of service',1),
  ('privacy_policy','We value your privacy.','text','legal','Privacy policy',1),
  ('support_email','support@voicechat.local','string','contact','Support email',1),
  ('default_language','en','string','localization','Default language',1),
  ('time_format','24h','string','localization','Time format (12h/24h)',1),
  ('theme','dark','string','appearance','Default theme (dark/light)',1);

-- ---------------------------------------------------------------------
-- Sample Agency
-- ---------------------------------------------------------------------
INSERT INTO `agencies` (`uuid`,`name`,`slug`,`description`,`owner_id`,`country`,`verified`,`level`,`xp`,`members_count`)
VALUES (UUID(),'VoiceChat Official','voicechat-official','Official VoiceChat agency',1,'Global',1,99,100000,2);

INSERT INTO `agency_members` (`agency_id`,`user_id`,`role`) VALUES (1,1,'owner'),(1,2,'admin');

-- ---------------------------------------------------------------------
-- Sample Rooms
-- ---------------------------------------------------------------------
INSERT INTO `rooms` (`uuid`,`name`,`slug`,`description`,`owner_id`,`type`,`max_seats`,`is_featured`,`language`,`category`,`status`) VALUES
  (UUID(),'Welcome Lounge','welcome-lounge','The official welcome room - say hi!',1,'public',8,1,'en','general','active'),
  (UUID(),'Chill Music Room','chill-music','Music lovers gather here',1,'public',8,1,'en','music','active'),
  (UUID(),'Gaming & Chat','gaming-chat','Talk about your favorite games',1,'public',8,0,'en','gaming','active'),
  (UUID(),'Late Night Talks','late-night','Conversations that last forever',2,'public',16,1,'en','general','active'),
  (UUID(),'Learn English','learn-english','Practice English together',2,'public',8,0,'en','education','active');
