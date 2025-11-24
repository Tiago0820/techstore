# Sistema de Especificações de Produtos - Instruções

## 📋 O que foi adicionado:

### 1. Nova tabela no banco de dados
- **Arquivo**: `database/product_specifications.sql`
- **Tabela**: `product_specifications`

### 2. Nova página de gestão
- **Arquivo**: `backoffice/admin_specifications.php`
- **Acesso**: Menu "Especificações" no backoffice

### 3. Atualização dos menus
- Todos os arquivos do backoffice agora têm link para "Especificações"

---

## 🚀 Como usar:

### Passo 1: Executar o SQL
1. Abra o phpMyAdmin (http://localhost/phpmyadmin)
2. Selecione o banco de dados `techshop`
3. Vá na aba "SQL"
4. Abra o arquivo `database/product_specifications.sql`
5. Copie e cole o conteúdo
6. Clique em "Executar"

### Passo 2: Acessar o Backoffice
1. Faça login como administrador
2. Acesse: http://localhost/techstore2/backoffice/admin_specifications.php
3. Ou clique no menu "Especificações"

### Passo 3: Adicionar Especificações
**Exemplo para iPhone 15 Pro:**

1. **Especificação de Armazenamento:**
   - Produto: iPhone 15 Pro
   - Nome: Armazenamento
   - Valor: 128GB
   - Modificador de Preço: 0.00
   - Stock: 10
   - Ordem: 1

2. **Adicionar variação 256GB:**
   - Produto: iPhone 15 Pro
   - Nome: Armazenamento
   - Valor: 256GB
   - Modificador de Preço: 100.00 (€100 mais caro)
   - Stock: 8
   - Ordem: 2

3. **Adicionar variação 512GB:**
   - Produto: iPhone 15 Pro
   - Nome: Armazenamento
   - Valor: 512GB
   - Modificador de Preço: 300.00 (€300 mais caro)
   - Stock: 5
   - Ordem: 3

4. **Especificação de Cor:**
   - Produto: iPhone 15 Pro
   - Nome: Cor
   - Valor: Preto Titânio
   - Modificador de Preço: 0.00
   - Stock: 7
   - Ordem: 4

---

## 📊 Funcionalidades:

### ✅ Adicionar Especificações
- Selecione o produto
- Defina nome da especificação (ex: Armazenamento, Cor, RAM)
- Defina o valor (ex: 128GB, Preto, 8GB)
- Adicione modificador de preço (se a variação custar mais ou menos)
- Defina o stock disponível
- Ordem de exibição (menor número aparece primeiro)

### ✅ Visualizar Especificações
- Agrupadas por produto
- Mostra preço base do produto
- Badges com informações:
  - Modificador de preço
  - Stock disponível
  - Ordem de exibição

### ✅ Editar Especificações
- Clique no ícone de editar
- Modifique os valores
- Salve as alterações

### ✅ Remover Especificações
- Clique no ícone de lixeira
- Confirme a remoção

---

## 💡 Exemplos de Uso:

### iPhone com diferentes armazenamentos:
- iPhone 15 Pro 128GB - Preço base
- iPhone 15 Pro 256GB - +€100
- iPhone 15 Pro 512GB - +€300

### Laptop com diferentes configurações:
- MacBook Pro M3 8GB RAM / 256GB SSD - Preço base
- MacBook Pro M3 16GB RAM / 512GB SSD - +€400
- MacBook Pro M3 32GB RAM / 1TB SSD - +€800

### Produtos com cores:
- Samsung Galaxy S24 Preto - Preço base
- Samsung Galaxy S24 Branco - Sem modificador
- Samsung Galaxy S24 Azul - Sem modificador

---

## 🔧 Campos Explicados:

- **Produto**: Qual produto terá esta especificação
- **Nome**: Tipo de especificação (Armazenamento, Cor, Tamanho, RAM, etc.)
- **Valor**: O valor específico (128GB, Preto, Grande, 8GB, etc.)
- **Modificador de Preço**: Quanto adicionar ou reduzir do preço base
  - Positivo: adiciona ao preço (ex: +100.00)
  - Negativo: reduz do preço (ex: -50.00)
  - Zero: mantém o preço base
- **Stock**: Quantidade disponível desta variação específica
- **Ordem**: Número para ordenar a exibição (menor = primeiro)

---

## 📝 Observações:

1. As especificações são opcionais - produtos sem especificações continuam funcionando normalmente
2. Você pode ter múltiplas especificações para o mesmo produto
3. O modificador de preço permite criar variações mais caras ou mais baratas
4. O stock é controlado por especificação, não apenas por produto
5. A ordem de exibição ajuda a organizar como as opções aparecem para o cliente

---

## 🎯 Próximos Passos (Futuro):

Para exibir as especificações na loja para os clientes, você precisará:
1. Modificar `product_detail.php` para mostrar as opções
2. Adicionar seletores (dropdowns) para o cliente escolher
3. Atualizar o preço dinamicamente quando selecionar uma opção
4. Modificar o carrinho para salvar a especificação escolhida

Mas por enquanto, o backoffice está completo e funcional! ✅
