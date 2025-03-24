from django.urls import path
from . import views

urlpatterns = [
    # ...existing code...
    path('connect/', views.connect, name='woocommerce-connect'),
    # ...existing code...
]
