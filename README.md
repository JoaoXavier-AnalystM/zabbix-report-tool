# Zabbix Report Tool

Interface web em PHP para gerar relatórios e gerenciar manutenções do Zabbix. Instala ao lado do servidor, sem modificar o Zabbix.

**Compatível com Zabbix 6.0 · 6.4 · 7.0 · 7.4**

---

## Instalação

### 1. Clone

```bash
git clone https://github.com/JoaoXavier-AnalystM/zabbix-report-tool.git /usr/share/zabbix/ui/zabbix-report
cd /usr/share/zabbix/ui/zabbix-report
```

### 2. Configure

Crie o arquivo de configuração a partir do template e edite a URL e fuso horário do seu Zabbix:

```bash
cp config.template.php config.php
```

Edite `config.php`:

```php
define('ZABBIX_URL', 'http://seu-zabbix/zabbix');   // URL do frontend
define('ZABBIX_TZ', 'America/Sao_Paulo');            // Fuso horário
```

> Só isso. Se usa LDAP/AD, configure também `ZBX_USER_PREFIX` e `ZBX_USER_SUFFIX`.

### 3. Dependências

```bash
composer install --no-dev --optimize-autoloader
```

> Instala o **dompdf**, necessário para gerar os PDFs.

### 4. Permissões

```bash
mkdir -p tmp logs
chmod 770 tmp logs
chown -R www-data:www-data tmp logs
```

### 5. Acesse

Abra `login.php` no navegador.

---

## Funcionalidades

### 📄 Relatórios PDF

1. Selecione hosts, grupos, templates e itens
2. Escolha o período (24h, 7d, 15d, 30d ou intervalo personalizado)
3. Gere o PDF com gráficos, índice e capa

O PDF inclui cabeçalho com logos, nome do usuário, período e data de geração.

### 📊 Exportação Excel — 4 tipos

| Relatório | O que contém |
|-----------|-------------|
| Lista de Hosts | Hosts monitorados com status |
| Inventário | SO, RAM, CPU mín/méd/pico, memória, discos, uptime |
| Problemas | Alertas e eventos do período |
| Picos | Valores de pico de CPU e memória por host |

Filtros de tempo: 24h · 7d · 15d · 30d ou intervalo livre.

### 📈 Relatório de SLA

Disponibilidade calculada a partir de eventos de trigger ICMP. Mostra SLA% real, downtime total e cada incidente com início/fim/duração. Exporta HTML ou CSV. Filtros de tempo rápidos: 24h, 7d, 15d, 30d.

### 🔧 Gestor de Manutenções

- Lista manutenções ativas, programadas e expiradas
- Cria manutenções com agendamento: única, diária, semanal, mensal
- Adiciona hosts a manutenções existentes
- Exporta lista de hosts por manutenção para CSV
- Pausa rápida de host ou item específico

### 🖥️ Explorador de Dados Recentes (Latest Data)

- Navega e filtra itens monitorados por hosts e grupos
- Autocompletar em tempo real
- Tabela paginada com filtro inline
- Exporta selecionados para PDF com um clique
- Botões rápidos de período: 24h, 7d, 15d, 30d

---

## Interface

- Temas claro/escuro com preferência persistente
- Imagem de fundo personalizada
- Responsivo
- **Português (Brasil) / Inglês** — troca via `?lang=pt-br` ou `?lang=en`
- Logos customizáveis no login (`assets/unicred.svg` + `assets/Zabbix_logo.png`)

---

## Segurança

- Autenticação via token da API Zabbix (senha nunca armazenada em sessão)
- Proteção CSRF em todos os formulários
- Sessão regenerada após login
- SSL verificável (`VERIFY_SSL = true`)

---

## Adicionar ao menu do Zabbix

Edite `/usr/share/zabbix/include/classes/helpers/CMenuHelper.php`, encontre:
```php
$submenu_reports = array_filter($submenu_reports);
```
Adicione acima:
```php
$submenu_reports[] = CWebUser::checkAccess(CRoleHelper::UI_REPORTS_SYSTEM_INFO)
    ? (new CMenuItem(_('Relatório PDF')))
          ->setUrl(new CUrl('zabbix-report/login.php'), true)
          ->setId('report_pdf')
          ->setAliases(['zabbix-report/chooser.php'])
    : null;
```

---

## Deploy com GitHub Actions

Workflow em `.github/workflows/deploy.yml`. Deploy automático via Tailscale + rsync. Requer secrets configurados no repositório:

`TS_AUTH_KEY` · `SSH_HOST` · `SSH_USER` · `SSH_KEY` · `DEPLOY_PATH` · `ZABBIX_URL` · `ZABBIX_TZ`
