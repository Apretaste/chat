--
-- Estructura de tabla para la tabla `_note`
--

CREATE TABLE `_note` (
  `id` int(11) NOT NULL,
  `from_user` int(11) DEFAULT NULL,
  `to_user` int(11) DEFAULT NULL,
  `text` varchar(500) DEFAULT NULL,
  `send_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_date` timestamp NULL DEFAULT NULL,
  `active` tinyint(2) NOT NULL DEFAULT '11'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Indices de la tabla `_note`
--
ALTER TABLE `_note`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user` (`from_user`),
  ADD KEY `to_user` (`to_user`);

--
-- AUTO_INCREMENT de la tabla `_note`
--
ALTER TABLE `_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
