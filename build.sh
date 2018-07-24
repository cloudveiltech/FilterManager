# Do build phase
cd CitadelManager
composer install --ignore-platform-reqs
npm install
typings install
cd -