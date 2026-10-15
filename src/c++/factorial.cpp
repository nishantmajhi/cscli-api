#include <iostream>
using namespace std;

long factorial(int n) {
    long product = 1;
    for (int i = n; i > 1; i--) {
        product *= i;
    }
    return product;
}

int main() {
    int num;
    cout << "Enter a number: ";
    cin >> num;

    if (num < 0) {
        cout << "Factorial of negative number can't be calculated!" << endl;
        return 1;
    }

    cout << "Factorial of " << num << " = " << factorial(num) << endl;
    return 0;
}
