<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_SizeChart: size guides on product pages. A chart is a table made in
 * the admin (Catalog > Size Charts) and shown to the products of chosen
 * categories, or to a product by its "Size Chart" attribute; the product
 * page gets a "Size guide" button that opens it in a dialog.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Zero_SizeChart', __DIR__);
