-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 21, 2014 at 12:49 PM
-- Server version: 5.5.25
-- PHP Version: 5.4.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `my_site`
--

-- --------------------------------------------------------

--
-- Table structure for table `misc`
--

CREATE TABLE IF NOT EXISTS `misc` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL COMMENT 'The key',
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `page` varchar(80) NOT NULL COMMENT 'The real page name in site folders',
  `path` varchar(255) NOT NULL DEFAULT 'pages/' COMMENT 'The page path in site folders',
  `url_en` varchar(255) NOT NULL DEFAULT 'a url' COMMENT 'The URL to access the page when rewrite engine is on',
  `url_fr` varchar(255) NOT NULL COMMENT 'The URL to access the page when rewrite engine is on',
  `title_en` varchar(255) NOT NULL COMMENT 'The page title',
  `title_fr` varchar(255) NOT NULL COMMENT 'The page title',
  `metaDesc_en` text NOT NULL COMMENT 'The page meta description En',
  `metaDesc_fr` text NOT NULL COMMENT 'The page meta description Fr',
  `metaKey_en` text NOT NULL COMMENT 'The page meta keywords En',
  `metaKey_fr` text NOT NULL COMMENT 'The page meta keywords Fr',
  `parent` varchar(255) NOT NULL DEFAULT 'home' COMMENT 'The parent real page name in site folders, for the breadcrumbs',
  `aliases` varchar(255) NOT NULL COMMENT 'Coma separated list of possible page aliases',
  `article` int(11) unsigned DEFAULT NULL COMMENT 'Article id if any',
  `icon` varchar(255) DEFAULT NULL COMMENT 'An icon to prepend to the page title (provide a glyph class)',
  PRIMARY KEY (`page`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`page`, `path`, `url_en`, `url_fr`, `title_en`, `title_fr`, `metaDesc_en`, `metaDesc_fr`, `metaKey_en`, `metaKey_fr`, `parent`, `aliases`, `article`, `icon`) VALUES
('article', 'pages/', '', '', 'New article', 'Nouvel article', '', '', '', '', 'home', '', NULL, ''),
('backstage', 'backstage/pages/', 'backstage/backstage', 'backstage/backstage', 'Backstage', 'Backstage', '', '', '', '', 'home', 'backstage/', NULL, ''),
('contact', 'pages/', 'contact', 'contact', 'Contact', 'Contact', '', '', '', '', 'home', '', NULL, 'i-mail'),
('create-new-page', 'backstage/pages/', 'backstage/create-a-new-page', 'backstage/creer-une-nouvelle-page', 'Create a new page', 'Créer une nouvelle page', '', '', '', '', 'backstage', '', NULL, ''),
('create-new-text', 'backstage/pages/', 'backstage/create-a-new-text', 'backstage/creer-un-nouveau-texte', 'Create a new text in database', 'Créer un nouveau texte en BDD', '', '', '', '', 'backstage', '', NULL, ''),
('forbidden', 'pages/', 'forbidden', 'forbidden', 'Forbidden (403)', 'Forbidden (403)', '', '', '', '', 'home', '', NULL, 'i-alert'),
('home', 'pages/', 'home', 'accueil', 'Home', 'Accueil', '', '', '', '', 'sitemap', '', NULL, ''),
('legal-terms', 'pages/', 'legal-terms', 'mentions-legales', 'Legal terms', 'Mentions légales', '', '', '', '', 'home', '', 1, ''),
('not-found', 'pages/', 'not-found', 'non-trouve', 'Not found (404)', 'Non trouvé (404)', '', '', '', '', 'home', '', NULL, 'i-alert'),
('sitemap', 'pages/', 'sitemap', 'plan-du-site', 'Sitemap', 'Plan du site', '', '', '', '', '', '', NULL, 'i-sitemap'),
('todo-list', 'backstage/pages/', 'backstage/todo-list', 'backstage/a-faire', 'TODO list', 'Tâches à faire', '', '', '', '', 'backstage', '', NULL, 'i-todo-2'),
('database', 'backstage/pages/', 'backstage/database', 'backstage/base-de-donnees', 'Database', 'Base de données', '', '', '', '', 'backstage', '', NULL, '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- --------------------------------------------------------


--
-- Table structure for table `texts`
--

CREATE TABLE `texts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text_en` varchar(255) NOT NULL,
  `text_fr` varchar(255) NOT NULL,
  `context` varchar(255) NOT NULL DEFAULT 'general',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `texts`
--

INSERT INTO `texts` (`id`, `text_en`, `text_fr`, `context`) VALUES
(1, 'To create a new page and insert it in database, please fill up the fields bellow.', 'Pour créer une nouvelle page et l''insérer en base de données, veuillez remplir les champs ci-dessous.', 'create-new-page'),
(2, 'Text', 'Texte', 'create-new-text'),
(3, 'Context', 'Contexte', 'create-new-text'),
(4, 'Type a keyword or select a page', 'Entrez un mot clé ou choisissez une page', 'create-new-text'),
(5, 'URL', 'URL', 'create-new-page'),
(6, 'Title', 'Titre', 'create-new-page'),
(7, 'Meta description', 'Meta description', 'create-new-page'),
(8, 'Meta keywords', 'Meta keywords', 'create-new-page'),
(9, 'Name', 'Nom', 'create-new-page'),
(10, 'Name of PHP file or article ID', 'Nom de fichier PHP ou ID article', 'create-new-page'),
(11, 'Path', 'Chemin', 'create-new-page'),
(12, 'Directory of the PHP file', 'Dossier du fichier PHP', 'create-new-page'),
(13, 'Parent', 'Parent', 'create-new-page'),
(14, 'Page type', 'Type de page', 'create-new-page'),
(15, 'Publish', 'Publier', 'general'),
(16, 'The new text was successfully added to the database with the id: #%d.', 'Le nouveau texte a bien été ajouté à la base de données, avec l''id : #%d.', 'create-new-text'),
(17, 'Cancel', 'Annuler', 'general'),
(18, 'Validate', 'Valider', 'general'),
(19, '© Copyright %s %d. All rights reserved.', '© Copyright %s %d. Tous droits reservés.', 'general'),
(20, 'Article contents in %s', 'Contenu de l''article en %s', 'create-new-page'),
(21, 'Created by %s the %s at %s.', 'Créé par %s le %s à %s.', 'article'),
(22, 'The page "%s" already exists in the database. Please use another page name.', 'La page "%s" existe déjà dans la base de données. Veuillez utiliser un autre nom de page.', 'create-new-page'),
(23, 'The page "%s" was created successfully.\r\nYou can see it here: <a href="%s">%s</a>.', 'La page "%s" a bien été créée.\r\nVous pouvez la voir ici : <a href="%s">%s</a>.', 'create-new-page'),
(24, 'Send', 'Envoyer', 'contact'),
(25, 'Message', 'Message', 'contact'),
(26, 'Last name', 'Nom', 'contact'),
(27, 'First name', 'Prénom', 'contact'),
(28, 'Email', 'Email', 'contact'),
(29, 'Have a question?', 'Vous avez une question ?', 'contact'),
(30, 'What would you like to know?', 'Que voudriez-vous savoir ?', 'contact'),
(31, 'To be sure you''re a human please activate the switch!', 'Si vous êtes un humain, activez l''interrupteur !', 'general'),
(32, 'The "%s" field contains invalid characters, please correct it before submitting.', 'Le champ "%s" contient des caractères non-autorisés. Merci de le corriger avant de resoumettre.', 'contact'),
(33, '[%s] %s %s has contacted you', '[%s] %s %s vous a contacté', 'contact'),
(34, 'Your message was sent successfully!', 'Votre message a bien été envoyé !', 'contact'),
(35, 'An error occured, your message has not been sent.', 'Une erreur est survenue, votre message n''a pas pu être envoyé.', 'contact'),
(36, 'You must enter 4 numbers and 2 letters.', 'Vous devez saisir 4 chiffres et 2 lettres.', 'general'),
(37, 'This article is not published', 'Cet article n''est pas publié', 'article'),
(38, '[b]We use cookies to enhance your user experience[/b]\r\nBy continuing to use our website, you agree to our use of cookies in order to offer you contents and services adapted to your needs.', '[b]Nous utilisons les cookies pour améliorer votre expérience utilisateur[/b]\r\nEn poursuivant votre navigation sur ce site, vous acceptez l''utilisation des cookies relatifs aux réseaux sociaux et à la mesure d''audience.', 'general'),
(39, 'Ok, I agree', 'J''accepte', 'general'),
(40, 'Learn more', 'En savoir plus', 'general'),
(41, 'You must fill all the mandatory fields.', 'Vous devez remplir tous les champs obligatoires.', 'general'),
(42, 'The duplicate insertion in database was rejected.', 'L''insertion d''un doublon en base de données a été refusée.', 'general'),
(43, 'Database manager', 'Gestionnaire de base de données', 'backstage'),
(44, 'Latest articles', 'Derniers articles', 'home'),
(45, 'Back to home', 'Retour à la page d''accueil', 'general'),
(46, 'd-m-Y', 'd/m/Y', 'date'),
(47, 'd-m-Y at H:i', 'd/m/Y à Hhi', 'date'),
(48, 'Monday', 'Lundi', 'date'),
(49, 'Tuesday', 'Mardi', 'date'),
(50, 'Wednesday', 'Mercredi', 'date'),
(51, 'Thursday', 'Jeudi', 'date'),
(52, 'Friday', 'Vendredi', 'date'),
(53, 'Saturday', 'Samedi', 'date'),
(54, 'Sunday', 'Dimanche', 'date'),
(55, 'January', 'Janvier', 'date'),
(56, 'February', 'Février', 'date'),
(57, 'March', 'Mars', 'date'),
(58, 'April', 'Avril', 'date'),
(59, 'May', 'Mai', 'date'),
(60, 'June', 'Juin', 'date'),
(61, 'July', 'Juillet', 'date'),
(62, 'August', 'Août', 'date'),
(63, 'September', 'Septembre', 'date'),
(64, 'October', 'Octobre', 'date'),
(65, 'November', 'Novembre', 'date'),
(66, 'December', 'Décembre', 'date'),
(67, 'The page "%s" was updated successfully.\nYou can see it here: <a href="%s">%s</a>.', 'La page "%s" a bien été modifiée.\nVous pouvez la voir ici : <a href="%s">%s</a>.', 'edit-new-page'),
(68, 'No row affected by the update request.', 'Aucune ligne n\'a été affectée par la requête de mise à jour.', 'edit-new-page'),
(69, 'The page \'%s\' does not exist in database.', 'La page \'%s\' n\'existe pas dans la base de données.', 'edit-new-page'),
(70, 'You''re in the backstage.', 'Vous voilà dans les coulisses.', 'backstage');
-- --------------------------------------------------------


--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Article id',
  `content_en` longtext NOT NULL COMMENT 'Article content in english',
  `content_fr` longtext NOT NULL COMMENT 'Article content in french',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Article creation timestamp',
  `author` int(11) unsigned NOT NULL COMMENT 'Author user id',
  `category` int(11) unsigned NOT NULL COMMENT 'Article category id',
  `image` varchar(255) NOT NULL COMMENT 'Article representative image for sharing and home page display',
  `published` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Boolean published or not'
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- --------------------------------------------------------

INSERT INTO `articles` (`id`, `content_en`, `content_fr`, `created`, `author`, `category`, `published`) VALUES
(1, '         <h2>Please, scroll down</h2>\n          <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n         <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n       ', '          <h2>Please, scroll down</h2>\n          <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n         <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n       ', '2014-11-09 22:29:30', 1, 1, 1),
(2, '         <h2>Please, scroll down</h2>\n          <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n         <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n       ', '          <h2>Please, scroll down</h2>\n          <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n         <p>I never did quite grasp him, though he endeavored to explain it to me upon numerous occasions.  I suggested telepathy, but he said no, that it was not telepathy since they could only communicate when in each others'' presence, nor could they talk with the Sagoths or the other inhabitants of Pellucidar by the same method they used to converse with one another.</p>\n          <p>"What they do," said Perry, "is to project their thoughts into the fourth dimension, when they become appreciable to the sixth sense of their listener.  Do I make myself quite clear?"</p>\n          <p>"You do not, Perry," I replied.  He shook his head in despair, and returned to his work.  They had set us to carrying a great accumulation of Maharan literature from one apartment to another, and there arranging it upon shelves.  I suggested to Perry that we were in the public library of Phutra, but later, as he commenced to discover the key to their written language, he assured me that we were handling the ancient archives of the race.</p>\n         <p>During this period my thoughts were continually upon Dian the Beautiful.  I was, of course, glad that she had escaped the Mahars, and the fate that had been suggested by the Sagoth who had threatened to purchase her upon our arrival at Phutra.  I often wondered if the little party of fugitives had been overtaken by the guards who had returned to search for them.  Sometimes I was not so sure but that I should have been more contented to know that Dian was here in Phutra, than to think of her at the mercy of Hooja the Sly One.  Ghak, Perry, and I often talked together of possible escape, but the Sarian was so steeped in his lifelong belief that no one could escape from the Mahars except by a miracle, that he was not much aid to us—his attitude was of one who waits for the miracle to come to him.</p>\n       ', '2014-11-09 22:29:30', 1, 2, 1);


--
-- Table structure for table `article_categories`
--

CREATE TABLE `article_categories` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `nameEn` varchar(255) NOT NULL,
  `nameFr` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

INSERT INTO `article_categories` (`id`, `name`, `nameEn`, `nameFr`) VALUES
(1, 'system', 'System', 'Système'),
(2, 'travel', 'Travel', 'Voyage');
MODIFY `id` int(10) unsigned NOT NULL,AUTO_INCREMENT=3;


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL COMMENT 'Case-insensitive user login used for logging in',
  `password` varchar(255) NOT NULL COMMENT 'Case-sensitive user password used for logging in',
  `firstName` varchar(255) NOT NULL COMMENT 'User real first name',
  `lastName` varchar(255) NOT NULL COMMENT 'User real last name',
  `email` varchar(255) NOT NULL COMMENT 'User activation-validated email address',
  `address1` varchar(255) NOT NULL,
  `address2` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `type` int(11) NOT NULL COMMENT 'User type (admin, guest, etc.) linked to the user_types table',
  `settings` text NOT NULL COMMENT 'Serialized object of user settings',
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
