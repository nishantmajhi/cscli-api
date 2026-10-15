/* Unit 4: Operators and Expression
 * Assignment operator (=, +=, -=, ...), conditional/ternary
 * operator (?:), and operator precedence in a mixed expression. */
#include <stdio.h>

int main(void) {
    int a = 10;
    a += 5;  /* a = a + 5 */
    a -= 2;  /* a = a - 2 */
    a *= 3;  /* a = a * 3 */

    printf("a after compound assignments = %d\n", a);

    int n = 7;
    const char *parity = (n % 2 == 0) ? "even" : "odd";
    printf("%d is %s\n", n, parity);

    int result = 2 + 3 * 4 - 1; /* precedence: * before +/- */
    printf("2 + 3 * 4 - 1 = %d\n", result);

    return 0;
}
