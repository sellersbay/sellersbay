from django.urls import path, include

urlpatterns = [
    # ...existing code...
    path('woocommerce/', include('woocommerce.urls')),
    # ...existing code...
]
