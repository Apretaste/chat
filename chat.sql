CREATE TABLE _note(
    id int(11) NOT NULL primary key AUTO_INCREMENT,
    from_user varchar(255),
    to_user varchar(255),
    text varchar(255),
    send_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);