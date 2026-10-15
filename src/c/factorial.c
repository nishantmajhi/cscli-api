#include <stdio.h>

long factorial(int n) {
    long product = 1;
    for (int i = n; i > 1; i--) {
        product *= i;
    }
    return product;
}

int main(void) {
    int num;
    printf("Enter a number: ");
    scanf("%d", &num);

    if (num < 0) {
        printf("Factorial of negative number can't be calculated!\n");
        return 1;
    }

    printf("Factorial of %d = %ld\n", num, factorial(num));
    return 0;
}
