<?php
namespace Tests\Feature;
use App\Models\{Product,User};
use Tests\TestCase;
class ProductVariantPagesTest extends TestCase {
  use \Illuminate\Foundation\Testing\DatabaseTransactions;
  public function test_trang_san_pham_gio_hang_thanh_toan_va_form_admin_hien_thi_phan_loai(): void {
    $p = Product::has('variants')->first();
    $u = User::factory()->create(['role'=>'user','email_verified_at'=>now()]);
    $a = User::where('role','admin')->first();
    $r = $this->actingAs($u)->get(route('products.show',$p->id)); $r->assertOk(); $r->assertSee('tl-variants',false);
    $this->actingAs($u)->withHeader('X-Requested-With','XMLHttpRequest')->postJson(route('cart.add',$p->id),['quantity'=>1,'variant_id'=>$p->variants->first()->id])->assertOk();
    $c=$this->get(route('cart.index')); $c->assertOk(); $c->assertSee('Phân loại:',false);
    $this->get(route('checkout.index'))->assertOk();
    $this->get(route('home'))->assertOk();
    $this->actingAs($a)->get(route('admin.products.edit',$p->id))->assertOk()->assertSee('tl-variant-rows',false);
    $this->actingAs($a)->get(route('admin.products.create'))->assertOk();
    // admin sửa: thêm biến thể mới + bỏ cái cũ
    $payload = $p->only(['name','category_id','price','quantity']);
    $payload['variants']=[['id'=>$p->variants->first()->id,'name'=>'Đổi tên','price_adjust'=>0,'quantity'=>7],['id'=>'','name'=>'Mới','price_adjust'=>5000,'quantity'=>3]];
    $this->actingAs($a)->put(route('admin.products.update',$p->id),$payload)->assertSessionHasNoErrors();
    $p->refresh()->load('variants');
    $this->assertSame(['Đổi tên','Mới'],$p->variants->pluck('name')->all());
    $this->assertSame(10,$p->quantity);
  }
}
