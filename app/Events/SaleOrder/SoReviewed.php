<?php

namespace App\Events\SaleOrder;

use App\Models\Voucher\SaleOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public SaleOrder $order) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->order->outlet_id) {
            $channels[] = new PrivateChannel(
                'sale-orders.review.outlet.'.$this->order->outlet_id
            );
        }

        if ($this->order->warehouse_id) {
            $channels[] = new PrivateChannel(
                'sale-orders.review.warehouse.'.$this->order->warehouse_id
            );
        }

        if ($this->order->branch_id) {
            $channels[] = new PrivateChannel(
                'sale-orders.review.branch.'.$this->order->branch_id
            );
        }

        // Always notify organization-level approvers
        $channels[] = new PrivateChannel(
            'sale-orders.review.organization.'.$this->order->organization_id
        );

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sale-order.reviewed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'              => $this->order->id,
            'document_number' => $this->order->document_number,
            'status'          => $this->order->status,
            'customer_id'     => $this->order->customer_id,
            'reviewed_at'     => optional($this->order->reviewed_at)->toDateTimeString(),
        ];
    }
}
