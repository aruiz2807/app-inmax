# INMAX

## Requisitos
- PHP `>= 8.3`
- Composer `2.x`
- Node.js `^20.19.0` o `>= 22.12.0` (Vite 7)
- npm
- Base de datos (MySQL, Postgres o SQLite)

## Instalacion (A, B, C)
A. Instalar dependencias
```bash
composer install
yarn install
```

B. Configurar entorno
```bash
cp .env.example .env
php artisan key:generate
```
Edita `.env` y configura la base de datos.

C. Migraciones y front
```bash
php artisan migrate
yarn dev
```

## Alternativa rapida
```bash
composer run setup
```

## Desarrollo
```bash
php artisan serve
yarn dev
```

## Build
```bash
yarn run build
```

## Deploy / Plesk
Este proyecto usa `yarn` como gestor de paquetes para frontend. No mezcles `npm` y `yarn` en el mismo deploy.

Instalacion limpia recomendada:
```bash
rm -rf node_modules
npm ci
npm run build
```

Si Plesk tiene configurado `yarn build`, cambialo por:
```bash
npm run build
```

Si despues de una instalacion vieja falla Rollup con `@rollup/rollup-linux-x64-gnu`, limpia e instala de nuevo con:
```bash
rm -rf node_modules
npm cache clean --force
npm ci
npm run build
```

## Tests
```bash
php artisan test
```
