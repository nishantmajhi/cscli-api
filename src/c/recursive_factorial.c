/* Unit 7: Functions
 * A recursive function (function that calls itself) computing
 * factorial, contrasted with the iterative version in factorial.c. */
#include <stdio.h>

long factorialRecursive(int n) {
    if (n <= 1) {
        return 1; /* base case */
    }
    return n * factorialRecursive(n - 1); /* recursive call */
}

int main(void) {
    int num;
    printf("Enter a number: ");
    scanf("%d", &num);

    if (num < 0) {
        printf("Factorial of negative number can't be calculated!\n");
        return 1;
    }

    printf("Factorial of %d = %ld\n", num, factorialRecursive(num));
    return 0;
}
