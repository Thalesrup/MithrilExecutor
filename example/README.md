# Exemplo de Uso do MithrilExecutor

Este diretório contém um exemplo completo de como utilizar a biblioteca MithrilExecutor em um cenário do mundo real ("dia a dia").

## Cenário
Imagine que você precisa gerar relatórios pesados em background para não travar a requisição do usuário.
Criamos a classe `ReportGenerator` para simular essa tarefa.

## Arquivos
- **`ReportGenerator.php`**: A classe de negócio que executa o trabalho pesado.
- **`bootstrap.php`**: Arquivo responsável por carregar as classes da sua aplicação e retornar o `ResolverInterface`.
- **`enqueue_job.php`**: Script que simula sua aplicação enviando um job para a fila.

## Como Rodar

### 1. Enviar o Job para a Fila
Execute o script que cria e enfileira o job:

```bash
php example/enqueue_job.php
```

Isso criará um arquivo JSON na pasta `storage/queue/pending`.

### 2. Executar o Worker
Inicie o worker para processar o job. Precisamos informar o `--bootstrap` para que ele saiba onde encontrar sua classe `ReportGenerator`.

```bash
php bin/mithril work --bootstrap=example/bootstrap.php
```

Ou em modo daemon (loop contínuo):

```bash
php bin/mithril daemon --bootstrap=example/bootstrap.php
```

### 3. Verificar o Resultado
Após o processamento, verifique a pasta `example/reports/`. Você verá um arquivo CSV gerado (ex: `report_financial_2025.csv`).

Também é possível ver os logs de execução e o status do job em `storage/results/`.

## Dicas para o Dia a Dia

1. **Bootstrap**: Em aplicações reais (como Laravel ou Symfony), seu `bootstrap.php` deve retornar um adaptador que use o container de injeção de dependência do framework. Se for um projeto simples, retornar `new ReflectionResolver()` é suficiente.
2. **Autoload**: Certifique-se de que suas classes de Job estão sendo carregadas (via Composer ou require manual no bootstrap).
