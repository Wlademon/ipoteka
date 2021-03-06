<?php

namespace App\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;
use App\Filters\PaymentFilter;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Class PaymentController
 * @package App\Http\Controllers
 */
class PaymentController extends BaseController
{
    /**
     * PaymentController constructor.
     * @param  Payment  $model
     * @param  PaymentFilter  $filter
     */
    public function __construct(Payment $model, PaymentFilter $filter)
    {
        $this->model = $model;
        $this->filter = $filter;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/v1/payment",
     *     operationId="/v1/payment(GET)",
     *     summary="Список оплат",
     *     tags={"Платежи"},
     *     @OA\Parameter(
     *         name="filter-contract_id",
     *         in="query",
     *         description="Выборка по договору",
     *         required=false,
     *         example="1",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter-order_id",
     *         in="query",
     *         description="Выборка по заказу",
     *         required=false,
     *         example="0e544646-4207-7ba1-8999-d9e304b053bd",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter-invoice_num",
     *         in="query",
     *         description="Выборка по номеру чека",
     *         required=false,
     *         example="158702176500009997",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает список оплат с возможностью фильтрации",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * Возвращает список оплат с возможностью фильтрацию.
     * @param  Request  $request
     * @return JsonResource
     */
    public function index(Request $request): JsonResource
    {
        $this->initRequest($request);

        $this->model = $this->model->orderBy('id');
        $this->model->filter($this->filter);

        return parent::index($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/payment",
     *     operationId="/v1/payment(POST)",
     *     summary="Добавить новую оплату",
     *     tags={"Платежи"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreatePaymentRequest")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Создает новую оплату",
     *         @OA\JsonContent(example="")
     *     )
     * )
     *
     * @param  CreatePaymentRequest  $request
     * @return JsonResource
     */
    public function store(CreatePaymentRequest $request): JsonResource
    {
        $payment = $this->model->fill($request->all());
        $payment->save();

        return self::successResponse($payment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/v1/payment/{id}",
     *     operationId="/v1/payment/{id}(PUT)",
     *     summary="Изменить оплату",
     *     tags={"Платежи"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор оплаты",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreatePaymentRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Обновляет существующую оплату",
     *         @OA\JsonContent(example="")
     *     )
     * )
     * @param  CreatePaymentRequest  $request
     * @param  int  $id
     * @return JsonResource
     */
    public function update(CreatePaymentRequest $request, int $id): JsonResource
    {
        $currentModel = $this->model::findOrFail($id);
        $attributes = $request->all();
        if (count($attributes) === 0) {
            $payment = $currentModel;
        } else {
            $payment = $currentModel->fill($attributes);
            $payment->save();
        }

        return self::successResponse($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/v1/payment/{id}",
     *     operationId="/v1/payment/{id}(DELETE)",
     *     summary="Удалить оплату",
     *     tags={"Платежи"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Идентификатор оплаты",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Удаляет существующую оплату",
     *         @OA\JsonContent(ref="#/components/schemas/Delete")
     *     )
     * )
     * @param  int  $id
     * @return JsonResource
     */
    public function destroy(int $id): JsonResource
    {
        return parent::destroy($id);
    }
}
