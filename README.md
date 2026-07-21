# Zabbix Report Tool — PDF, Excel, SLA & Gestor de Manutenções

Aplicação PHP que se instala junto ao servidor Zabbix e adiciona uma interface moderna de relatórios e manutenções. Sem necessidade de modificar o Zabbix.

**Compatível com Zabbix 6.0 · 6.4 · 7.0 · 7.4**

---

## ✨ Funcionalidades

### 📄 Gerador de Relatórios PDF
- Assistente de 5 etapas: selecione hosts → modelos → itens → período → gerar
- Pré-visualização de gráficos com 6 tipos (linha, área, barra, spline, degrau, dispersão)
- Barra de progresso durante a geração

### 📊 Exportação Excel — 4 tipos de relatório
| Relatório | Descrição |
|-----------|-----------|
| Lista Geral de Hosts | Todos os hosts monitorados com status |
| Inventário Detalhado | SO, RAM, CPU mín/méd/pico, memória, discos, uptime |
| Relatório de Problemas | Alertas e eventos do período selecionado |
| Relatório de Picos | Valores de pico de CPU e memória por host |

### 📈 Relatório de SLA
- Disponibilidade baseada em **eventos de trigger ICMP** (não apenas ping)
- Exibe SLA% real, tempo total de inatividade e cada incidente com início/fim/duração
- Exporta para visualização HTML ou CSV
- Compatível com Zabbix 6.0 a 7.4

### 🔧 Gestor de Manutenções
- Lista de manutenções com status em tempo real (Ativa / Programada / Expirada)
- Criação de manutenções com agendamento completo:
  - Única, Diária, Semanal, Mensal
  - Modo mensal com **Dia do mês** e **Dia da semana** (igual à interface do Zabbix)
- Adicionar hosts a manutenções existentes
- Exportar lista de hosts por manutenção para CSV
- Busca de hosts com autocompletar

### 🖥️ Explorador de Dados Recentes
- Navegar e filtrar todos os itens monitorados por hosts e grupos
- Autocompletar em tempo real para hosts e grupos
- Tabela paginada com filtro inline
- Exportação para PDF com um clique

---

## 🎨 Interface

- Temas claro/escuro modernos com preferência persistente
- Suporte a imagem de fundo personalizada
- Totalmente responsivo
- Bilíngue: **Português (Brasil) / Inglês**
- Topbar fixa com cards em glassmorphism

---

## 🔒 Segurança

- Autenticação via API do Zabbix com token (senha não é armazenada em sessão)
- Proteção CSRF em todos os formulários
- Dados permanecem apenas no banco de dados do Zabbix

---

## 📚 Instalação

### Requisitos
- PHP 7.2 ou superior
- Extensões PHP: curl, gd, json, mbstring, xml, zip, zlib, fileinfo
- Composer (para dependência dompdf)
- Permissão de escrita nos diretórios `tmp/` e `logs/`

### Configuração Rápida

1. Copie a pasta do projeto para um diretório acessível pelo servidor web (ex: `/var/www/html/zabbix-report/`)

2. Crie o `config.php` a partir do template:
   ```bash
   cp config.template.php config.php
   ```

3. Edite o `config.php` e defina a URL do Zabbix e o fuso horário:
   ```php
   define('ZABBIX_URL', 'http://seu-servidor-zabbix/zabbix');
   define('ZABBIX_TZ', 'America/Sao_Paulo');
   ```

4. Instale as dependências do Composer:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. Acesse `login.php` pelo navegador.

### Opcional: Adicionar ao menu do Zabbix

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

### LDAP / Active Directory

Configure prefixo/sufixo no `config.php` se seu Zabbix usa LDAP:
```php
define('ZBX_USER_PREFIX', 'DOMINIO\\');    // Active Directory
define('ZBX_USER_SUFFIX', '@dominio.local'); // Email UPN
```

---

## 🌐 Idiomas

Português (Brasil) e Inglês já inclusos. Para adicionar novos idiomas, crie `lang/XX.php` e adicione o código em `SUPPORTED_LANGS` no arquivo `lib/i18n.php`.
