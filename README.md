# freimguork-skeleton

Plantilla base para arrancar un proyecto nuevo sobre `freimguork-core` +
`freimguork-appacman` (panel admin incluido). Basada en la estructura de
`cuina-de-profit-local`, sin nada específico de ese proyecto.

## Cómo usarla para un proyecto nuevo (p. ej. "tv-tracker")

1. Copia esta carpeta con el nombre del proyecto nuevo (`<nombre>-local`) y
   haz `git init` allí (no un fork de este repo — cada proyecto es su propio
   historial).
2. Busca y reemplaza los placeholders:
   - `{{project-slug}}` → slug del proyecto (`tv-tracker`)
   - `{{project name}}` → nombre legible (`TV Tracker`)
   - `{{project description}}` → descripción corta
   - `{{project-domain}}` → dominio real de producción
3. Copia cada `config/**/*.php.dist` a su homónimo sin `.dist` y rellena
   valores reales (nunca commitees estos ficheros - ya están en
   `.gitignore`):
   - `config/dev/db.php`, `config/prod/db.php`
   - `config/dev/keys.php`, `config/prod/keys.php` (genera el secreto con
     `php -r "echo bin2hex(random_bytes(32));"`, uno **distinto** por
     entorno)
   - `config/keys.php`, `config/mail.php` si el proyecto los necesita
4. Crea la base de datos e importa `db.sql` (esquema + datos mínimos de
   Appacman - bloques, tipos de campo, perfiles y permisos - sin ningún
   usuario admin todavía).
5. `composer install` (publica también los assets de Appacman/AdminLTE en
   `web/` vía el script `AssetPublisher`).
6. Da de alta el vhost local apuntando `DocumentRoot` a `web/`.

## Primer usuario admin

`appacman_user.name`/`email` van cifrados (TwoWay) y `password` hasheado
(OneWay) bajo el secreto de **este** proyecto (`config/dev/keys.php`), así
que no se puede seedear un admin genérico en `db.sql` - hay que generarlo ya
con la clave real del proyecto. Con `config/dev/keys.php` ya rellenado:

```php
<?php
require 'vendor/autoload.php';
const DIR_ROOT = './';
$_ENV['IS_DEV'] = true;

$name    = 'Admin';
$email   = 'admin@example.com';
$plain   = '<contraseña-real>';
$created = date('Y-m-d H:i:s');
$context = fn($field) => "1_{$created}_{$field}"; // <id>_<created>_<field>

$db = new PDO('mysql:host=mariadb;dbname=<db-name>', '<db-user>', '<db-password>');
$db->prepare('
    INSERT INTO appacman_user (name, email, password, id_appacman_user_profile, created)
    VALUES (:name, :email, :password, 2, :created)
')->execute([
    'name'     => Core\Model\Encryptor\TwoWay::encrypt($name, $context('name')),
    'email'    => Core\Model\Encryptor\TwoWay::encrypt($email, $context('email')),
    'password' => Core\Model\Encryptor\OneWay::encrypt($plain, $context('password')),
    'created'  => $created,
]);
```

(perfil `2` = SuperAdministrador, ya con permisos completos sobre
`appacman_user` en el `db.sql` seed). Ajusta `<id>` en el `$context` al
`id_appacman_user` real que vaya a asignar el `AUTO_INCREMENT` (normalmente
`1` para el primer usuario).

## Estructura

- `config/` - credenciales por entorno (`dev/`/`prod/`, gitignored + `.dist`
  commiteados) y `projects.php` (mapa de sub-proyectos: `wallaby` = Appacman,
  `{lang}` = Web público)
- `web/` - document root servido (front controllers, `.htaccess`, assets
  estáticos, uploads)
- `src/Web/` - controladores/vistas del sitio público (`Home`,
  `DefaultController` de ejemplo)
- `src/Appacman/` - punto de extensión para overrides de Appacman (formularios
  custom, etc.) - vacío de partida
- `src/cache/` - cache de rutas compiladas en prod (gitignored)
- `db.sql` - esquema Appacman mínimo, sin usuario admin (ver arriba)

## Qué NO incluye todavía

- Conversión a Composer plugin (`AssetPublisher` sigue siendo un script wireado
  a mano en `composer.json` - decisión pendiente de aplicar de forma
  consistente a toda la familia `freimguork-*`)
- Tests / CI
- `Cronjob`/`Import` sub-proyectos (añádelos a `config/projects.php` si el
  proyecto los necesita, siguiendo el mismo patrón que `wallaby`/`{lang}`)
