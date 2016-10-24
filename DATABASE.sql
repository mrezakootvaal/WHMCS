
CREATE TABLE IF NOT EXISTS `tblZarinPalLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` varchar(32) NOT NULL,
  `Amount` varchar(32) NOT NULL,
  `Authority` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
