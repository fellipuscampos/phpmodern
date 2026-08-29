# Fase 0 — checklist de verificação manual

Objetivo: provar que um componente (`OrderStatusBadge`) com estado, renderizado
no servidor, se atualiza na tela via push (SSE) — sem F5 e sem polling do
navegador — tanto em modo **bridge** (site legado simulado) quanto em modo
**kernel** (app criada do zero), reaproveitando a mesma classe de componente.

## 1. Instalar dependências

```bash
composer install                      # raiz do workspace (tooling + testes)
composer install -d apps/legacy-demo
composer install -d apps/starter-kernel
```

## 2. Subir o hub de push

```bash
php packages/core/push-hub/bin/hub.php
```

Deve imprimir `push-hub listening on http://127.0.0.1:8081` e ficar rodando.

## 3. Testar o modo bridge (site legado simulado)

```bash
php -S 127.0.0.1:8000 -t apps/legacy-demo
```

1. Abra `http://127.0.0.1:8000/` — deve mostrar "Pedido #42: pendente".
2. Clique em "Avançar status".
3. O badge deve mudar para "confirmado" **sem recarregar a página**.
4. No DevTools → Network, confirme que a única conexão contínua é o
   `EventSource` (`push-hub-client.js.php` → hub) — nenhum request repetido
   de polling.

## 4. Testar o modo kernel (app criada do zero)

```bash
php -S 127.0.0.1:8001 -t apps/starter-kernel/public
```

1. Abra `http://127.0.0.1:8001/` — mesmo pedido #42, porque o banco é
   compartilhado (`var/demo.sqlite`).
2. Clique em "Avançar status" — o badge atualiza pelo mesmo mecanismo de push.
3. Confirme que `apps/starter-kernel/app/Components/OrderStatusBadge.php` e o
   arquivo carregado pelo `legacy-demo` são o **mesmo arquivo** (o
   `legacy-demo/index.php` faz `require` direto dele) — nenhuma duplicação de
   código do componente entre os dois modos.

## 5. Testes automatizados

```bash
composer test       # PHPUnit: component-engine, orm, push-hub
composer analyse     # PHPStan nível 8
```

## Critério de sucesso

- [ ] Badge atualiza em tempo real nos dois modos, sem polling.
- [ ] O componente é o mesmo arquivo/classe nos dois modos.
- [ ] Testes unitários passam.
- [ ] Nenhuma das duas apps precisou de Swoole/RoadRunner — o hub roda como
      processo CLI independente.
