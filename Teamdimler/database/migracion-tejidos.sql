USE teamdimler;

ALTER TABLE productos
    MODIFY categoria ENUM('crochet', 'tejidos', 'manualidades', 'libreria', 'costura') NOT NULL;
