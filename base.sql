CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(30),
    direccion VARCHAR(255)
) ENGINE=InnoDB;

ALTER TABLE productos ENGINE=InnoDB;

ALTER TABLE productos
ADD categoria_id INT,
ADD proveedor_id INT;

ALTER TABLE productos
ADD CONSTRAINT fk_productos_categorias
FOREIGN KEY (categoria_id) REFERENCES categorias(id);

ALTER TABLE productos
ADD CONSTRAINT fk_productos_proveedores
FOREIGN KEY (proveedor_id) REFERENCES proveedores(id);