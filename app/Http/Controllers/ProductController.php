<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Product;
use Illuminate\Http\Request;
use App\Http\Requests\CreateProductRequest;
class ProductController extends Controller
{
     /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::with('image')->latest()->paginate(15);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductRequest $request)
    {
        $getdata = $request->all();
        $getdata['user_id'] = auth()->user()->id;
        $getdata['shope_id'] = 1;
        $product = Product::create($getdata);
        if ($request->hasFile('image')) {
            $imageName = time().'_'.$getdata['image']->getClientOriginalName();
            $imageName = preg_replace("/ /", "-", $imageName);
            $getdata['image']->move(public_path('images'), $imageName);
            $getdata['url'] = $imageName;
            $product->image()->create($getdata);
        }
        return redirect()->route('products.index')->with('successMassage','Product was added.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(CreateProductRequest $request, Product $product)
    {
        $product->update($request->all());
        if ($request->hasFile('image')) {
            $imageName = time().'_'.$request->file('image')->getClientOriginalName();
            $getData = preg_replace('/ /', '-', $imageName);

            if (!empty($product->image)) {
                if (file_exists($oldImage = public_path().$product->image->url)){
                    unlink($oldImage);
                }
                $product->image()->update(['url'=>$getData]);
            } else {
                $product->image()->create(['url'=>$getData]);
            }

            $request->image->move(public_path('images'),$getData);

        }
        return redirect()->route('products.index')->with('successMassage', 'The product has been successfully updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Product $product
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Product $product)
    {
        //delete all of price and detach all of the shops
        if (!blank($product->shops)){
            foreach ($product->shops as $shop){
                $product->price()->where('shop_id',$shop->id)->first()->delete();
            }
            $product->shops()->detach();
        }

        //Delete if there are any images
        if (!empty($product->image) && file_exists($imageName = public_path().$product->image->url)) {
            unlink($imageName);
            $product->image()->delete();
        }

        //Delete the product now
        $product->delete();

        //return the response
        return redirect()->route('products.index')->with('successMassage','The product has been successfully deleted.');
    }
}
