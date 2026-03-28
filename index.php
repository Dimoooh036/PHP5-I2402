<?php
declare(strict_types=1);

/**
 * Интерфейс хранилища
 */
interface TransactionStorageInterface
{
    public function addTransaction(Transaction $transaction): void;
    public function removeTransactionById(int $id): void;
    /** @return Transaction[] */
    public function getAllTransactions(): array;
    public function findById(int $id): ?Transaction;
}

/**
 * Класс транзакции
 */
class Transaction
{
    private int $id;
    private DateTime $date;
    private float $amount;
    private string $description;
    private string $merchant;

    public function __construct(int $id, string $date, float $amount, string $description, string $merchant)
    {
        $this->id = $id;
        $this->date = new DateTime($date);
        $this->amount = $amount;
        $this->description = $description;
        $this->merchant = $merchant;
    }

    public function getId(): int { return $this->id; }
    public function getDate(): DateTime { return $this->date; }
    public function getAmount(): float { return $this->amount; }
    public function getDescription(): string { return $this->description; }
    public function getMerchant(): string { return $this->merchant; }
    public function getDaysSinceTransaction(): int { return $this->date->diff(new DateTime())->days; }
}

/**
 * Репозиторий
 */
class TransactionRepository implements TransactionStorageInterface
{
    private array $transactions = [];

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function removeTransactionById(int $id): void
    {
        foreach ($this->transactions as $key => $transaction) {
            if ($transaction->getId() === $id) {
                unset($this->transactions[$key]);
            }
        }
        $this->transactions = array_values($this->transactions);
    }

    public function getAllTransactions(): array
    {
        return $this->transactions;
    }

    public function findById(int $id): ?Transaction
    {
        foreach ($this->transactions as $transaction) {
            if ($transaction->getId() === $id) return $transaction;
        }
        return null;
    }
}

/**
 * Менеджер транзакций — добавление всех функций
 */
class TransactionManager
{
    public function __construct(private TransactionStorageInterface $repository) {}

    // Добавление и удаление
    public function addTransaction(Transaction $transaction): void { $this->repository->addTransaction($transaction); }
    public function removeTransactionById(int $id): void { $this->repository->removeTransactionById($id); }
    public function findTransactionById(int $id): ?Transaction { return $this->repository->findById($id); }

    // Вычисления
    public function calculateTotalAmount(): float
    {
        return array_sum(array_map(fn($t) => $t->getAmount(), $this->repository->getAllTransactions()));
    }

    public function calculateTotalAmountByDateRange(string $startDate, string $endDate): float
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        return array_sum(array_map(
            fn($t) => ($t->getDate() >= $start && $t->getDate() <= $end) ? $t->getAmount() : 0,
            $this->repository->getAllTransactions()
        ));
    }

    public function countTransactionsByMerchant(string $merchant): int
    {
        return count(array_filter($this->repository->getAllTransactions(), fn($t) => $t->getMerchant() === $merchant));
    }

    // Сортировки
    public function sortTransactionsByDate(): array
    {
        $arr = $this->repository->getAllTransactions();
        usort($arr, fn($a,$b) => $a->getDate() <=> $b->getDate());
        return $arr;
    }

    public function sortTransactionsByAmountDesc(): array
    {
        $arr = $this->repository->getAllTransactions();
        usort($arr, fn($a,$b) => $b->getAmount() <=> $a->getAmount());
        return $arr;
    }

    // Получить все транзакции
    public function getAllTransactions(): array { return $this->repository->getAllTransactions(); }
}

/**
 * Рендер таблицы
 */
final class TransactionTableRenderer
{
    public function render(array $transactions): string
    {
        $html = "<table border='1' cellpadding='6'>";
        $html .= "<tr><th>ID</th><th>Дата</th><th>Сумма</th><th>Описание</th><th>Получатель</th><th>Категория</th><th>Дней прошло</th></tr>";

        foreach ($transactions as $t) {
            $category = $this->getMerchantCategory($t->getMerchant());
            $html .= "<tr>
                <td>{$t->getId()}</td>
                <td>{$t->getDate()->format('Y-m-d')}</td>
                <td>{$t->getAmount()}</td>
                <td>{$t->getDescription()}</td>
                <td>{$t->getMerchant()}</td>
                <td>{$category}</td>
                <td>{$t->getDaysSinceTransaction()}</td>
            </tr>";
        }

        $html .= "</table>";
        return $html;
    }

    private function getMerchantCategory(string $merchant): string
    {
        return match ($merchant) {
            'Amazon', 'eMAG' => 'Shopping',
            'Netflix', 'Spotify' => 'Subscription',
            'McDonalds' => 'Food',
            default => 'Other'
        };
    }
}

/**
 * Начальные данные
 */
$repository = new TransactionRepository();
$manager = new TransactionManager($repository);

$transactions = [
    new Transaction(1,'2026-03-01',120.5,'Books','Amazon'),
    new Transaction(2,'2026-02-18',15.9,'Music','Spotify'),
    new Transaction(3,'2026-03-10',45.0,'Food','McDonalds'),
    new Transaction(4,'2026-01-05',300,'Electronics','eMAG'),
    new Transaction(5,'2026-03-12',12.99,'Movie','Netflix'),
    new Transaction(6,'2026-02-01',80,'Clothes','Amazon'),
    new Transaction(7,'2026-03-15',25,'Lunch','McDonalds'),
    new Transaction(8,'2026-01-20',9.99,'Subscription','Spotify'),
    new Transaction(9,'2026-02-25',220,'Monitor','eMAG'),
    new Transaction(10,'2026-03-18',18,'Snack','McDonalds'),
];

foreach ($transactions as $t) {
    $manager->addTransaction($t); // добавляем через менеджер
}

$renderer = new TransactionTableRenderer();

echo "<h2>Общая сумма: ".$manager->calculateTotalAmount()."</h2>";
echo $renderer->render($manager->sortTransactionsByDate());