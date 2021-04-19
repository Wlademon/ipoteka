<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use App\Filters\PaymentFilter;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Payments;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function __construct(Payments $model, PaymentFilter $filter)
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
     * @param Request $request
     * @return array|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->initRequest($request);
        $this->setTotalCount(Payments::count());

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
     * @param CreatePaymentRequest $request
     * @return Payments
     */
    public function store(CreatePaymentRequest $request)
    {
        $payment = $this->model->fill($request->all());
        $payment->save();

        return $payment;
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/v1/payment/{id}",
     *     operationId="/v1/payment/{id}(PUT)",
     *     summary="Обновить оплату",
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
     * @param CreatePaymentRequest $request
     * @param int $id
     * @return \App\Models\BaseModel
     */
    public function update(CreatePaymentRequest $request, $id)
    {
        $currentModel = $this->model::find($id);
        if (!$currentModel) {
            return null;
        }
        $attributes = $request->all();
        if (count($attributes) == 0) {
            $payment = $currentModel;
        } else {
            $payment = $currentModel->fill($attributes);
            $payment->save();
        }

        return $payment;
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
