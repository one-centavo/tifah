# 3. Diseño de base de datos

## Diccionario de datos

### Tabla: `users`
Esta tabla centraliza la información de identidad, credenciales de acceso y trazabilidad de los usuarios.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único y autoincremental del usuario. |
| `first_name` | VARCHAR(100) | NOT NULL | Primer nombre del usuario. |
| `middle_name` | VARCHAR(100) | NULL | Segundo nombre del usuario (opcional). |
| `last_name` | VARCHAR(100) | NOT NULL | Primer apellido del usuario. |
| `second_last_name` | VARCHAR(100) | NULL | Segundo apellido del usuario (opcional). |
| `phone_number` | VARCHAR(15) | NOT NULL | Número de contacto telefónico o celular. |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Correo electrónico (usado como identificador de login). |
| `password` | VARCHAR(255) | NOT NULL | Hash de la contraseña del usuario (Bcrypt/Argon2). |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |
| `created_by` | BIGINT | FK (users.id) | ID del usuario que creó este registro. |
| `updated_by` | BIGINT | FK (users.id) | ID del último usuario que modificó este registro. |
| `deleted_at` | TIMESTAMP | NULL | Fecha y hora del borrado lógico. |
| `deleted_by` | BIGINT | FK (users.id) | ID del usuario que ejecutó el borrado lógico. |

### Tabla: `categories`
Define la clasificación de los productos y establece restricciones logísticas iniciales (cadena de frío o control especial).

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental de la categoría. |
| `name` | VARCHAR(255) | UNIQUE, NOT NULL | Nombre descriptivo (ej: Analgésicos, Antibióticos). |
| `description` | VARCHAR(255) | NULL | Información adicional sobre el alcance de la categoría. |
| `is_cold_chain` | TINYINT | NOT NULL, DEFAULT 0 | Booleano: indica si requiere cadena de frío (1: Sí, 0: No). |
| `is_special_control` | TINYINT | NOT NULL, DEFAULT 0 | Booleano: indica si es de control especial (1: Sí, 0: No). |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | ID del usuario que registró la categoría. |
| `updated_by` | BIGINT | FK (users.id), NULL | ID del último usuario que modificó el registro. |
| `deleted_at` | TIMESTAMP | NULL | Fecha y hora del borrado lógico. |
| `deleted_by` | BIGINT | FK (users.id), NULL | ID del usuario que ejecutó el borrado lógico. |



### Tabla: `laboratories`
Almacena el catálogo de laboratorios farmacéuticos o fabricantes de los productos.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del laboratorio. |
| `name` | VARCHAR(255) | UNIQUE, NOT NULL | Nombre comercial  del laboratorio. |
| `description` | VARCHAR(255) | NULL | Información adicional o notas sobre el fabricante. |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |
| `created_by` | BIGINT | FK (users.id), NULL | ID del usuario que registró el laboratorio. |
| `updated_by` | BIGINT | FK (users.id), NULL | ID del último usuario que modificó el registro. |
| `deleted_at` | TIMESTAMP | NULL | Fecha y hora del borrado logico |
| `deleted_by` | BIGINT | FK (users.id), NULL | ID del usuario que ejecutó el borrado lógico. |

### Tabla: `sanitary_registries`
Almacena los registros sanitarios (ej. INVIMA) asociados a los laboratorios, permitiendo controlar la vigencia legal de los productos para su comercialización.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del registro sanitario. |
| `registration_number` | VARCHAR(50) | UNIQUE, NOT NULL | Número o código alfanumérico oficial del registro sanitario. |
| `laboratory_id` | BIGINT | FK (laboratories.id), NOT NULL | ID del laboratorio titular|
| `expiration_date` | DATE | NOT NULL | Fecha de vencimiento del registro sanitario. |
| `status` | ENUM | DEFAULT 'valid' | Estado legal del registro: `expired` (vencido), `valid` (vigente), `under_renewal` (en renovación). |
| `description` | TEXT | NULL | Observaciones detalladas o especificaciones del registro sanitario. |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |
| `created_by` | BIGINT | FK (users.id), NULL | ID del usuario que registró el documento sanitario. |
| `updated_by` | BIGINT | FK (users.id), NULL | ID del último usuario que modificó el registro. |
| `deleted_at` | TIMESTAMP | NOT NULL | Marca de tiempo para la ejecución del borrado lógico. |
| `deleted_by` | BIGINT | FK (users.id), NULL | ID del usuario que ejecutó el borrado lógico. |

### Tabla: `containers`
Catálogo maestro que define el empaque externo o envase físico que contiene al medicamento.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del tipo de contenedor. |
| `name` | VARCHAR(100) | UNIQUE, NOT NULL | Nombre del envase (ej: Frasco, Caja, Ampolleta, Blíster). |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |

---

### Tabla: `content_units`
Catálogo maestro que define la unidad física o forma de dosificación comercial que viene dentro del envase.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental de la unidad de contenido. |
| `name` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre de la unidad física interna (ej: Tabletas, Cápsulas, Sobres, ml). |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |

---

### Tabla: `concentration_units`
Catálogo maestro que define la unidad de medida para la fuerza o potencia del componente químico (principio activo).

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental de la unidad de concentración. |
| `name` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre completo de la unidad de potencia (ej: Miligramo, Microgramo, Unidad Internacional). |
| `symbol` | VARCHAR(10) | UNIQUE, NOT NULL | Símbolo técnico de la fuerza química (ej: mg, g, mcg, UI, %). |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |

### Tabla: `suppliers`
Almacena el catálogo de proveedores de la farmacia. Guarda la información legal, datos tributarios (como el NIT y el Dígito de Verificación) e información de contacto comercial 

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del proveedor. |
| `nit` | VARCHAR(20) | UNIQUE, NOT NULL | Número de Identificación Tributaria (NIT) de la empresa proveedora. |
| `dv` | TINYINT | NOT NULL | Dígito de Verificación del NIT (calculado para la validación de la DIAN). |
| `name` | VARCHAR(150) | NOT NULL | Razón social o nombre comercial del proveedor. |
| `contact_person` | VARCHAR(100) | NOT NULL | Nombre completo de la persona o asesor de contacto comercial. |
| `phone_number` | VARCHAR(20) | NOT NULL | Número telefónico o celular de contacto con el proveedor. |
| `email` | VARCHAR(255) | NOT NULL | Correo electrónico comercial para el envío de pedidos o facturación. |
| `address` | VARCHAR(255) | NOT NULL | Dirección física de las instalaciones o despachos del proveedor. |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | ID del usuario que registró al proveedor en el sistema. |
| `updated_by` | BIGINT | FK (users.id), NULL | ID del último usuario que modificó los datos del proveedor. |
| `deleted_at` | TIMESTAMP | NOT NULL | Marca de tiempo para la ejecución del borrado lógico |
| `deleted_by` | BIGINT | FK (users.id), NULL | ID del usuario que ejecutó el borrado lógico. |

### Tabla: `customers`
Almacena la información de los clientes (personas jurídicas o naturales) del sistema. Registra datos de identificación tributaria nacional, ubicación, contacto y estado comercial.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del cliente. |
| `nit` | VARCHAR(20) | NOT NULL | Número de Identificación Tributaria (NIT) o documento de identidad del cliente. |
| `dv` | TINYINT | NOT NULL | Dígito de Verificación del NIT (obligatorio para la validación del RUT). |
| `name` | VARCHAR(255) | NOT NULL | Razón social de la empresa o nombre completo del cliente. |
| `city` | VARCHAR(100) | NOT NULL | Municipio o ciudad de residencia o ubicación comercial del cliente. |
| `address` | VARCHAR(255) | NOT NULL | Dirección física de entrega o correspondencia. |
| `phone_number` | VARCHAR(20) | NOT NULL | Número telefónico o celular de contacto. |
| `email` | VARCHAR(255) | NOT NULL | Correo electrónico para el envío de facturación electrónica y notificaciones. |
| `is_active` | TINYINT | DEFAULT 1 | Estado comercial del cliente (1: Activo/Apto para crédito o venta, 0: Inactivo). |
| `created_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la creación del registro. |
| `updated_at` | TIMESTAMP | NOT NULL | Marca de tiempo de la última actualización. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | ID del usuario que registró al cliente en el sistema. |
| `updated_by` | BIGINT | FK (users.id), NULL | ID del último usuario que modificó los datos del cliente. |
| `deleted_at` | TIMESTAMP | NOT NULL | Marca de tiempo para la ejecución del borrado lógico |
| `deleted_by` | BIGINT | FK (users.id), NULL | ID del usuario que ejecutó el borrado lógico. |

### Tabla: `medicines`
Es la entidad central del catálogo de productos de la farmacia. Consolida las propiedades comerciales, farmacológicas, logísticas y legales de cada medicamento, enlazando múltiples tablas maestras mediante llaves foráneas. Incluye reglas de negocio críticas (como alertas de stock y precios) y auditoría completa con soporte para borrado lógico.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del medicamento. |
| `category_id` | BIGINT | FK (categories.id), NOT NULL | ID de la categoría o grupo terapéutico al que pertenece. |
| `laboratory_id` | BIGINT | FK (laboratories.id), NOT NULL | ID del laboratorio fabricante o titular del producto. |
| `sanitary_registry_id` | BIGINT | FK (sanitary_registries.id), NOT NULL | ID del registro sanitario legal (ej: INVIMA) asociado. |
| `name` | VARCHAR(255) | NOT NULL | Nombre comercial del medicamento. |
| `generic_name` | VARCHAR(255) | NULL | Nombre genérico o principio activo del medicamento. |
| `concentration_value` | DECIMAL(10,2) | NOT NULL | Valor numérico de la fuerza o potencia química (ej: 500.00, 20.00). |
| `concentration_unit_id` | BIGINT | FK (concentration_units.id), NOT NULL | ID de la unidad de medida de la concentración (ej: mg, g, %). |
| `container_id` | BIGINT | FK (containers.id), NOT NULL | ID del empaque externo o envase físico (ej: Frasco, Caja). |
| `content_quantity` | INTEGER | NOT NULL | Cantidad numérica de unidades contenidas en el envase (ej: 30, 100). |
| `content_unit_id` | BIGINT | FK (content_units.id), NOT NULL | ID de la unidad física que viene dentro del envase (ej: Tabletas, ml). |
| `is_cold_chain` | TINYINT | DEFAULT 0 | Define específicamente si el producto requiere cadena de frío, permitiendo excepciones a la regla de la categoría. |
| `is_special_control` | TINYINT | DEFAULT 0 | Define específicamente si es de control especial, permitiendo excepciones a la regla de la categoría. |
| `min_stock` | INTEGER | NOT NULL, DEFAULT 5 | Cantidad mínima requerida en inventario antes de disparar alertas de reabastecimiento. |
| `selling_price` | DECIMAL(10,2) | NOT NULL | Precio base de venta al público por unidad comercial (caja/frasco). |
| `description` | VARCHAR(255) | NULL | Notas adicionales, indicaciones breves o especificaciones del producto. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Marca de tiempo automática de la creación del registro. |
| `updated_at` | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | Marca de tiempo automática de la última actualización (nulo hasta el primer cambio). |
| `created_by` | BIGINT | FK (users.id), NOT NULL | ID del usuario que registró el medicamento en el sistema. |
| `updated_by` | BIGINT | FK (users.id), NULL | ID del último usuario que modificó los datos del medicamento. |
| `deleted_at` | TIMESTAMP | NULL | Marca de tiempo para borrado lógico . Si es NULL, el registro está activo. |
| `deleted_by` | BIGINT | FK (users.id), NULL | ID del usuario que ejecutó el borrado lógico. |
    
### Tabla: `medicine_barcodes`
Almacena los códigos de barras asociados a los medicamentos. Permite que un solo producto farmacéutico pueda tener múltiples códigos de barras registrados (por ejemplo, cambios de presentación o códigos del proveedor) y define cuál es el principal para las lecturas rápidas en el inventario.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único autoincremental del registro del código de barras. |
| `medicine_id` | BIGINT | FK (medicines.id), NOT NULL | ID del medicamento al cual está asociado el código de barras. |
| `barcode` | VARCHAR(255) | UNIQUE, NOT NULL | Cadena de texto única que representa el código numérico o alfanumérico extraído del lector de barras. |
| `is_main` | TINYINT | DEFAULT 0 | Booleano: indica si este es el código de barras principal o predeterminado para el producto (1: Sí, 0: No). |



### Tabla: `purchase_orders`
Representa el encabezado de las órdenes de compra. Es el documento maestro que gestiona la relación con el proveedor y el estado global del pedido.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único de la orden de compra. |
| `supplier_id` | BIGINT | FK (suppliers.id), NOT NULL | ID del proveedor al que se le solicita la mercancía. |
| `status` | ENUM | NOT NULL, DEFAULT 'pending' | Estados: `pending` (pendiente), `received` (recibido), `cancelled` (cancelado). |
| `expected_date` | DATE | NULL | Fecha estimada de entrega por parte del proveedor. |
| `received_at` | TIMESTAMP | NULL | Fecha y hora real en la que se recibió el pedido en bodega. |
| `total_estimated` | DECIMAL(10,2) | NOT NULL | Suma total proyectada de los detalles de la compra. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de registro de la orden. |
| `updated_at` | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | Fecha de la última modificación. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | Usuario que generó la orden. |
| `updated_by` | BIGINT | FK (users.id), NULL | Usuario que realizó la última actualización. |
| `deleted_at` | TIMESTAMP | NULL | Marca de tiempo para borrado lógico (anulación de orden). |
| `deleted_by` | BIGINT | FK (users.id), NULL | Usuario que anuló la orden. |

### Tabla: `purchase_order_details`
Contiene el desglose de productos solicitados en cada orden de compra, permitiendo registrar costos unitarios que pueden variar entre pedidos.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único del detalle. |
| `purchase_order_id` | BIGINT | FK (purchase_orders.id), NOT NULL | Relación con la cabecera de la orden de compra. |
| `medicine_id` | BIGINT | FK (medicines.id), NOT NULL | ID del medicamento solicitado (unidad comercial). |
| `quantity` | INTEGER | NOT NULL | Cantidad de unidades (cajas/frascos) pedidas. |
| `unit_cost` | DECIMAL(10,2) | NOT NULL | Costo de adquisición por unidad pactado para esta orden. |
| `subtotal` | DECIMAL(10,2) | NOT NULL | Valor total por línea (`quantity` * `unit_cost`). |


### Tabla: `lots`
Es la entidad encargada de gestionar el inventario físico mediante el control de lotes. Implementa la lógica FEFO (First Expired, First Out) y permite rastrear la rentabilidad de cada producto basándose en su costo de adquisición específico.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único del lote en el sistema. |
| `medicine_id` | BIGINT | FK (medicines.id), NOT NULL | Relación con el medicamento (unidad comercial) al que pertenece el lote. |
| `purchase_order_id` | BIGINT | FK (purchase_orders.id), NOT NULL | ID de la orden de compra que originó la entrada de este lote. |
| `batch_number` | VARCHAR(255) | NOT NULL | Código alfanumérico asignado por el fabricante para trazabilidad legal. |
| `expiration_date` | DATE | NOT NULL | Fecha de vencimiento. Clave para la gestión de alertas de proximidad. |
| `current_quantity` | INTEGER | NOT NULL | Cantidad de unidades comerciales disponibles actualmente en bodega. |
| `initial_quantity` | INTEGER | NOT NULL | Cantidad total recibida originalmente al momento del ingreso. |
| `reception_date` | DATE | NOT NULL | Fecha física en la que se recibió el lote (puede diferir de la creación en sistema). |
| `unit_purchase_price`| DECIMAL(10,2) | NOT NULL | Costo de compra por unidad comercial para este lote específico. |
| `status` | ENUM | DEFAULT 'active' | Estado del lote: `active` (disponible), `blocked` (en cuarentena), `damaged` (avería). |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha y hora de registro en el sistema. |
| `updated_at` | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | Fecha de la última actualización de cantidad o estado. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | Usuario que realizó el ingreso de la mercancía. |
| `updated_by` | BIGINT | FK (users.id), NULL | Último usuario que modificó el registro del lote. |
| `deleted_at` | TIMESTAMP | NULL | Marca de tiempo para borrado lógico (Soft Delete). |
| `deleted_by` | BIGINT | FK (users.id), NULL | Usuario que ejecutó la eliminación lógica. |


### Tabla: `bills`
Representa el encabezado de las facturas de venta. Es el documento legal que agrupa la transacción comercial con el cliente y controla el estado general de la venta.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único (número de factura). |
| `id_customer` | BIGINT | FK (customers.id), NOT NULL | ID del cliente que realiza la compra. |
| `status` | ENUM | DEFAULT 'draft' | Estados: `draft` (borrador), `active` (emitida), `annulled` (anulada). |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha y hora de emisión de la factura. |
| `updated_at` | TIMESTAMP | NULL, ON UPDATE CURRENT_TIMESTAMP | Fecha de la última modificación del estado. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | Usuario (vendedor/administrador) que generó la factura. |
| `updated_by` | BIGINT | FK (users.id), NULL | Usuario que realizó modificaciones o anulaciones. |

### Tabla: `bill_details`
Contiene el desglose de productos de cada factura. Se vincula directamente a los lotes para garantizar que el inventario se descuente correctamente según la fecha de vencimiento.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único del detalle de factura. |
| `bill_id` | BIGINT | FK (bills.id), NOT NULL | Relación con la factura maestra. |
| `lot_id` | BIGINT | FK (lots.id), NOT NULL | ID del lote específico de donde se extrajo la mercancía. |
| `quantity` | INTEGER | NOT NULL | Cantidad de unidades comerciales vendidas. |
| `unit_price` | DECIMAL(10,2) | NOT NULL | Precio de venta final por unidad (capturado en el momento de la venta). |
| `subtotal` | DECIMAL(10,2) | NOT NULL | Resultado de la operación: $quantity \times unit\_price$. |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Marca de tiempo del registro del item. |

### Tabla: `inventory_movements`
Es el libro auxiliar de almacén (Kárdex). Registra cada entrada, salida o ajuste manual, permitiendo reconstruir el historial de stock de cualquier lote en el tiempo.

| Columna | Tipo de Dato | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, AI, NOT NULL | Identificador único del movimiento. |
| `lot_id` | BIGINT | FK (lots.id), NOT NULL | Lote afectado por el movimiento. |
| `type` | ENUM | NOT NULL | Tipo de flujo: `entry` (entrada), `exit` (salida), `adjustment` (corrección). |
| `quantity` | INTEGER | NOT NULL | Cantidad de unidades que entraron o salieron. |
| `previous_balance`| INTEGER | NOT NULL | Stock que existía en el lote justo antes del movimiento. |
| `new_balance` | INTEGER | NOT NULL | Stock resultante después de aplicar el movimiento. |
| `concept` | VARCHAR(255) | NOT NULL | Descripción del motivo (ej: "Venta Factura #105", "Ajuste por avería"). |
| `reference_id` | BIGINT | NOT NULL | ID del documento relacionado (id de factura, orden de compra, etc.). |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha y hora exacta de la afectación del inventario. |
| `created_by` | BIGINT | FK (users.id), NOT NULL | Usuario que ejecutó o autorizó el movimiento. |
