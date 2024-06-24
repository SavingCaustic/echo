<?php
enum OrderStatusEnum {
    case Pending;
    case Approved;
    case Rejected;
    //case FINAL;

    function getCount() {
        return 3;
    }
}

class Order {
    private OrderStatusEnum $statusEnum;

    public function __construct(OrderStatusEnum $status) {
        $this->statusEnum = $status;
        die(serialize($this->statusEnum));
    }

    public function getStatus(): OrderStatusEnum {
        return $this->statusEnum;
    }

    public function setStatus(OrderStatusEnum $status): void {
        $this->statusEnum = $status;
    }
}

// Usage
$order = new Order(OrderStatusEnum::Pending);
//$order->setStatus(OrderStatusEnum::Approved);
if ($order->getStatus() == OrderStatusEnum::Approved) {
    echo 'Fine, we are approved';
} else {
    echo 'Not approved yet';
}

echo 'we have ' . OrderStatusEnum::FINAL . ' options of status';